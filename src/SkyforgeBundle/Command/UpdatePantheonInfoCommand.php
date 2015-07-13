<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 22.11.2014
 */

namespace Erliz\SkyforgeBundle\Command;


use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Erliz\SilexCommonBundle\Command\ApplicationAwareCommand;
use Erliz\SkyforgeBundle\Entity\Community;
use Erliz\SkyforgeBundle\Entity\CommunityInterface;
use Erliz\SkyforgeBundle\Entity\Pantheon;
use Erliz\SkyforgeBundle\Entity\Player;
use Erliz\SkyforgeBundle\Repository\PlayerRepository;
use Erliz\SkyforgeBundle\Service\ParseService;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePantheonInfoCommand extends ApplicationAwareCommand
{
    const TYPE_PANTHEON  = 'pantheon';
    const TYPE_COMMUNITY = 'community';

    /** @var ParseService */
    private $parseService;
    /** @var EntityManager */
    private $em;
    /** @var PlayerRepository */
    private $playerRepository;
    /** @var EntityRepository */
    private $pantheonRepository;
    /** @var EntityRepository */
    private $communityRepository;
    /** @var Logger */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('skyforge:pantheon:update')
            ->setDefinition($this->createDefinition())
            ->setDescription('Update pantheon members count')
            ->setHelp(<<<EOF
The <info>%command.name%</info> load all data from pantheon community page and update players with it
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getProjectApplication();
        $this->logger = $this->getLogger();

        $this->em = $app['orm.em'];
        $this->parseService = $app['parse.skyforge.service'];
        $this->parseService->setAuthData($app['config']['skyforge']['statistic']);
        $this->playerRepository = $this->em->getRepository('Erliz\SkyforgeBundle\Entity\Player');
        $this->pantheonRepository = $this->em->getRepository('Erliz\SkyforgeBundle\Entity\Pantheon');
        $this->communityRepository = $this->em->getRepository('Erliz\SkyforgeBundle\Entity\Community');

        $lockFilePath = '/home/sites/erliz.ru/app/cache/curl/parse.lock';

        if (is_file($lockFilePath)) {
            throw new \RuntimeException('Another parse in progress');
        } else {
            file_put_contents($lockFilePath, getmypid());
        }

        if ($communityId = $input->getOption('id')) {
            $community = $this->communityRepository->find($communityId);
            $type = $this::TYPE_COMMUNITY;
            if (!$community) {
                $community = $this->pantheonRepository->find($communityId);
                $type = $this::TYPE_PANTHEON;
            }
            if (!$community) {
                $this->logger->addInfo(sprintf('Community with id %s not found in db', $communityId));
            } else {
                $this->updateCommunityMembers($community, $output, $type);
            }
        }
        if ($input->getOption('pantheons')) {
            /** @var Pantheon $community */
            foreach ($this->pantheonRepository->findAll() as $community) {
                $this->updateCommunityMembers($community, $output, $this::TYPE_PANTHEON);
            }
        }
        if ($input->getOption('communities')) {
            /** @var Community $community */
            foreach ($this->communityRepository->findAll() as $community) {
                $this->updateCommunityMembers($community, $output, $this::TYPE_COMMUNITY);
            }
        }

        unlink($lockFilePath);
    }

    private function makeCommunityMembersUrl($id)
    {
        return sprintf("https://portal.sf.mail.ru/community/members/%s", $id);
    }

    private function makeCommunityMembersMoreUrl($id)
    {
        return sprintf('https://portal.sf.mail.ru/community/members.morebutton:loadbunch?t:ac=%s', $id);
    }

    /**
     * @param CommunityInterface $community
     * @param OutputInterface    $output
     * @param string             $type
     */
    private function updateCommunityMembers(CommunityInterface $community, OutputInterface $output, $type)
    {
        $this->logger->addInfo(sprintf('Checking %s "%s" with %s', $type, $community->getName(), $community->getId()));
        $members = array();
        try {
            for($page = 1; $page <= 20; $page++) {
                $responseMessage = $this->parseService->getPage(
                    $this->makeCommunityMembersMoreUrl($community->getId()),
                    true,
                    $this->makeCommunityMembersUrl($community->getId()),
                    array('t:zone' => 'bunchZone', 'bunchIndex' => $page)
                );
                $response = json_decode($responseMessage);
                if (!$response) {
                    $this->logger->addInfo(sprintf('Empty page %s', $page));
                    break;
                }
                $pageMembers = $this->parseService->getMembersFromCommunityPage($response->content);
                $this->logger->addInfo(sprintf('Page %s parsed successful, get %s members', $page, count($pageMembers)));
                $members = $members + $pageMembers;
                usleep(rand(500, 1500) * 1000);
            }
        } catch (RuntimeException $e) {
            $this->logger->addInfo('Exception: ' . $e->getMessage() . ' ' . $e->getCode());
        }

        if ($type == $this::TYPE_PANTHEON) {
            $dbMembers = $this->playerRepository->findBy(array('pantheon' => $community->getId()));
        } elseif ($type == $this::TYPE_COMMUNITY) {
            $dbMembers = $this->playerRepository->findByCommunity($community);
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown community type "%s" to find members', $type));
        }

        /** @var Player $member */
        foreach ($dbMembers as $member) {
            if ($type == $this::TYPE_PANTHEON) {
                $member->removePantheon();
            } elseif ($type == $this::TYPE_COMMUNITY) {
                $member->getCommunities()->removeElement($community);
            } else {
                throw new \InvalidArgumentException(sprintf('Unknown community type "%s" to remove community', $type));
            }
        }

        $this->em->flush();

        foreach ($members as $parsedMember) {
            $player = $this->playerRepository->find($parsedMember->id);
            if (!$player) {
                $player = new Player();
                $player->setId($parsedMember->id);
                $this->em->persist($player);
            }
            if ($parsedMember->name) {
                $player->setName($parsedMember->name);
            }
            if ($parsedMember->nick) {
                $player->setNick($parsedMember->nick);
            }

            $community->addMember($player);
            if ($type == $this::TYPE_PANTHEON) {
                $player->setPantheon($community);
            } elseif ($type == $this::TYPE_COMMUNITY) {
                $player->getCommunities()->add($community);
            } else {
                throw new \InvalidArgumentException(sprintf('Unknown community type "%s" to add for member', $type));
            }
        }

        $community->setUpdatedAt(new DateTime());
        $this->em->flush();
    }

    private function createDefinition()
    {
        return array(
            new InputOption('pantheons', 'p', InputOption::VALUE_NONE, 'flag to parse pantheons'),
            new InputOption('communities', 'c', InputOption::VALUE_NONE, 'flag to parse communities'),
            new InputOption('id', 'i', InputOption::VALUE_REQUIRED, 'id of community to parse')
        );
    }
}

