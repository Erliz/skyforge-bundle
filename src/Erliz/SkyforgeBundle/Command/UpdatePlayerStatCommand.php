<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 22.11.2014
 */

namespace Erliz\SkyforgeBundle\Command;


use Doctrine\ORM\EntityManager;
use Erliz\SilexCommonBundle\Command\ApplicationAwareCommand;
use Erliz\SkyforgeBundle\Entity\Community;
use Erliz\SkyforgeBundle\Entity\CommunityInterface;
use Erliz\SkyforgeBundle\Entity\Pantheon;
use Erliz\SkyforgeBundle\Service\StatService;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePlayerStatCommand extends ApplicationAwareCommand
{
    /** @var StatService */
    private $statService;
    /** @var Logger */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('skyforge:players:update')
            ->setDefinition($this->createDefinition())
            ->setDescription('Dump all data from Data Base to fixtures file')
            ->setHelp(<<<EOF
The <info>%command.name%</info> load all data from Data Base to fixtures file
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getProjectApplication();
        $this->logger = $this->getLogger();

        /** @var EntityManager $em */
        $em = $app['orm.em'];
        /** @var StatService $statService */
        $this->statService = $app['stat.skyforge.service'];
        $pantheonRepository = $em->getRepository('Erliz\SkyforgeBundle\Entity\Pantheon');
        $communityRepository = $em->getRepository('Erliz\SkyforgeBundle\Entity\Community');

        $lockFilePath = '/home/sites/erliz.ru/app/cache/curl/parse.lock';

        if (is_file($lockFilePath)) {
            throw new RuntimeException('Another parse in progress');
        } else {
            file_put_contents($lockFilePath, getmypid());
        }
        if ($playerId = $input->getOption('avatar')) {
            $this->statService->updatePlayer($playerId);
        }
        if ($communityId = $input->getOption('id')) {
            $community = $communityRepository->find($communityId);
            if (!$community) {
                $community = $pantheonRepository->find($communityId);
            }
            if (!$community) {
                $this->logger->addInfo(sprintf('Community with id %s not found in db', $communityId));
            } else {
                $this->updateCommunityMembers($community, $output);
            }
        }
        if ($input->getOption('pantheons')) {
            /** @var Pantheon $community */
            foreach ($pantheonRepository->findAll() as $community) {
                $this->updateCommunityMembers($community, $output);
            }
        }
        if ($input->getOption('communities')) {
            /** @var Community $community */
            foreach ($communityRepository->findAll() as $community) {
                $this->updateCommunityMembers($community, $output);
            }
        }

        unlink($lockFilePath);
    }

    /**
     * @param CommunityInterface $community
     * @param Output             $output
     */
    private function updateCommunityMembers(CommunityInterface $community, Output $output)
    {
        $this->logger->addInfo(sprintf('Checking community "%s" with %s', $community->getName(), $community->getId()));

        $today = new \DateTime('-4 hour');
        $loopTimeStart = microtime(true);

        if ($output->isVerbose()) {
            $this->logger->addInfo(sprintf('Found %s members', count($community->getMembers())));
        }
        foreach ($community->getMembers() as $player) {
            $memberTimeStart = microtime(true);
            $this->logger->addInfo(sprintf('Checking user "%s" with id "%s"', $player->getNick(), $player->getId()));
            $alreadyParseCheckTime = microtime(true);
            if (count($player->getDateStat()) && $player->getDateStat()->first()->getDate()->format('Y:m:d') == $today->format('Y:m:d')) {
                $this->logger->addInfo(sprintf('Player "%s" with id %s already parsed today', $player->getNick(), $player->getId()));
                if ($output->isVerbose()) {
                    $this->logger->addInfo(sprintf('Already parse check take %s sec to process', microtime(true) - $alreadyParseCheckTime));
                }
                continue;
            }
            if ($output->isVerbose()) {
                $this->logger->addInfo(sprintf('Already parse check take %s sec to process', microtime(true) - $alreadyParseCheckTime));
            }
            $playerUpdateTimeStart = microtime(true);
            try {
                $this->statService->updatePlayer($player->getId());
            } catch (RuntimeException $e){
                if ($e->getCode() == 403) {
                    $this->logger->addInfo(sprintf('Fail to update player "%s" with id %s', $player->getNick(), $player->getId()));
                } else {
                    throw $e;
                }
            }
            if ($output->isVerbose()) {
                $this->logger->addInfo(sprintf('Player parse take %s sec to process', microtime(true) - $playerUpdateTimeStart));
            }
            $sleepMicroTime = rand(500, 1500) * 1000;
            if ($output->isVerbose()) {
                $this->logger->addInfo(sprintf('Member take %s sec to process', microtime(true) - $memberTimeStart));
                $this->logger->addInfo(sprintf('Sleep for %s sec', $sleepMicroTime / 1000 / 1000));
            }
            usleep($sleepMicroTime);
        }
        if ($output->isVerbose()) {
            $this->logger->addInfo(sprintf('Loop take %s sec to process', microtime(true) - $loopTimeStart));
        }
    }

    /**
     * @return array
     */
    private function createDefinition()
    {
        return array(
            new InputOption('pantheons', 'p', InputOption::VALUE_NONE, 'flag to parse pantheons'),
            new InputOption('communities', 'c', InputOption::VALUE_NONE, 'flag to parse communities'),
            new InputOption('id', 'i', InputOption::VALUE_REQUIRED, 'id of community to parse'),
            new InputOption('avatar', 'a', InputOption::VALUE_REQUIRED, 'id of avatar to parse')
        );
    }
}
