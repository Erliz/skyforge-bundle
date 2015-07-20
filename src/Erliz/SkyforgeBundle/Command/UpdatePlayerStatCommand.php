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
use Erliz\SkyforgeBundle\Service\RegionService;
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
    /** @var RegionService */
    private $regionService;
    /** @var EntityManager */
    private $em;

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

        $this->regionService = $app['region.skyforge.service'];
        $this->regionService->setRegion($input->getOption('region'));

        /** @var EntityManager $em */
        $this->em = $app['orm.ems'][$this->regionService->getDbConnectionNameByRegion()];
        /** @var StatService $statService */
        $this->statService = $app['stat.skyforge.service'];
        $pantheonRepository = $this->em->getRepository('Erliz\SkyforgeBundle\Entity\Pantheon');
        $communityRepository = $this->em->getRepository('Erliz\SkyforgeBundle\Entity\Community');

        $lockFilePath = $app['config']['app']['path'].'/cache/curl/parse.lock';

        if (is_file($lockFilePath)) {
            throw new RuntimeException('Another parse in progress');
        } else {
            file_put_contents($lockFilePath, getmypid());
        }
        if ($playerId = $input->getOption('avatar')) {
            $this->statService->updatePlayer($playerId, true);
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
        $lastId = $input->getOption('lastId');
        if ($input->getOption('pantheons')) {
            /** @var Pantheon $community */
            foreach ($pantheonRepository->findAll() as $community) {
                if ($community->getId() == $lastId){
                    $lastId = false;
                }
                if ($lastId) {
                    continue;
                }
                $this->updateCommunityMembers($community, $output);
                $this->flush();
            }
        } else if ($input->getOption('communities')) {
            /** @var Community $community */
            foreach ($communityRepository->findAll() as $community) {
                if ($community->getId() == $lastId){
                    $lastId = false;
                }
                if ($lastId) {
                    continue;
                }
                $this->updateCommunityMembers($community, $output);
                $this->flush();
            }
        }

        unlink($lockFilePath);
    }

    private function flush()
    {
        $flushTimeStart = microtime(true);
        $this->em->flush();
        $this->logger->addInfo(sprintf('Flush db take %s sec to proceed', microtime(true) - $flushTimeStart ));
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
        $failsCount = 0 ;
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
                    $failsCount ++;
                    if ($failsCount >= 10) {
                        throw $e;
                    }
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
            new InputOption('region', 'r', InputOption::VALUE_REQUIRED, 'region of skyforge project'),
            new InputOption('pantheons', 'p', InputOption::VALUE_NONE, 'flag to parse pantheons'),
            new InputOption('communities', 'c', InputOption::VALUE_NONE, 'flag to parse communities'),
            new InputOption('id', 'i', InputOption::VALUE_REQUIRED, 'id of community to parse'),
            new InputOption('lastId', 'l', InputOption::VALUE_REQUIRED, 'id of community to parse'),
            new InputOption('avatar', 'a', InputOption::VALUE_REQUIRED, 'id of avatar to parse')
        );
    }
}
