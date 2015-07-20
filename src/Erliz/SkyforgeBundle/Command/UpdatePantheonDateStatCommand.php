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
use Erliz\SkyforgeBundle\Entity\Pantheon;
use Erliz\SkyforgeBundle\Entity\PantheonDateStat;
use Erliz\SkyforgeBundle\Entity\Player;
use Erliz\SkyforgeBundle\Entity\PlayerDateStat;
use Erliz\SkyforgeBundle\Repository\PlayerRepository;
use Erliz\SkyforgeBundle\Service\ParseService;
use Erliz\SkyforgeBundle\Service\RegionService;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePantheonDateStatCommand extends ApplicationAwareCommand
{
    /** @var RegionService */
    private $regionService;
    /** @var EntityManager */
    private $em;
    /** @var EntityRepository */
    private $pantheonRepository;
    /** @var Logger */
    private $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('skyforge:pantheon:datestat')
            ->setDefinition($this->createDefinition())
            ->setDescription('Update pantheon datestat')
            ->setHelp(<<<EOF
The <info>%command.name%</info> get data from members and aggregate it
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

        $this->em = $app['orm.ems'][$this->regionService->getDbConnectionNameByRegion()];

        $this->pantheonRepository = $this->em->getRepository('Erliz\SkyforgeBundle\Entity\Pantheon');

        if ($input->getOption('id')) {
            $this->updateCommunityDateStat($this->pantheonRepository->find($input->getOption('id')), $output);
        } else {

            /** @var Pantheon $community */
            foreach ($this->pantheonRepository->findAll() as $community) {
                $this->updateCommunityDateStat($community, $output);
            }
        }

//        $this->em->flush();

    }

    /**
     * @param Pantheon $community
     * @param OutputInterface    $output
     */
    private function updateCommunityDateStat(Pantheon $community, OutputInterface $output)
    {
        $this->logger->addInfo(sprintf('Update community "%s" with id %s', $community->getId(), $community->getName()));

        $today = new \DateTime('-4 hour');
        /** @var PantheonDateStat[] $communityDateStat */
        $communityDateStat = $community->getDateStat();

        if (count($communityDateStat) && $communityDateStat->first()->getDate()->format('Y:m:d') == $today->format('Y:m:d')) {
            $this->logger->addInfo(sprintf('Community "%s" with id %s already parsed today', $community->getName(), $community->getId()));
            return;
        }

        $aggregator = array(
            'sumPrestige' => 0,
            'maxPrestige' => 0,

            'pveSumMobKills' => 0,
            'pveSumBossKills' => 0,
            'pveSumDeaths' => 0,

            'pvpSumTime' => 0,
            'pvpSumKills' => 0,
            'pvpSumDeaths' => 0,
            'pvpSumAssists' => 0
        );

        /** @var Player $member */
        foreach ($community->getMembers() as $member) {
            if(!count($member->getDateStat())) {
                continue;
            }

            /** @var PlayerDateStat $lastPlayerDateStat */
            $lastPlayerDateStat = $member->getDateStat()->first();

            $aggregator['sumPrestige'] += $lastPlayerDateStat->getMaxPrestige();
            $aggregator['maxPrestige'] = $aggregator['maxPrestige'] < $lastPlayerDateStat->getMaxPrestige() ? $lastPlayerDateStat->getMaxPrestige() : $aggregator['maxPrestige'];

            $aggregator['pvpSumTime'] += $lastPlayerDateStat->getPvpTime();

//            $aggregator['pveSumTime'] += $lastPlayerDateStat->getPveTime();
            foreach ($member->getRoleStat() as $role) {
                $aggregator['pveSumMobKills'] += $role->getPveMobKills();
                $aggregator['pveSumBossKills'] += $role->getPveBossKills();
                $aggregator['pveSumDeaths'] += $role->getPveDeaths();

                $aggregator['pvpSumKills'] += $role->getPvpKills();
                $aggregator['pvpSumDeaths'] += $role->getPvpDeaths();
                $aggregator['pvpSumAssists'] += $role->getPvpAssists();
            }
        }


        $newPantheonDateStat = new PantheonDateStat();

        $newPantheonDateStat
            ->setPantheon($community)
            ->setDate($today)

            ->setSumPrestige($aggregator['sumPrestige'])
            ->setAvgPrestige(round($aggregator['sumPrestige'] / count($community->getMembers())))
            ->setMembersCount(count($community->getMembers()))
            ->setMaxPrestige($aggregator['maxPrestige'])

            ->setPveSumMobKills($aggregator['pveSumMobKills'])
            ->setPveSumBossKills($aggregator['pveSumBossKills'])
            ->setPveSumDeaths($aggregator['pveSumDeaths'])

            ->setPvpSumTime($aggregator['pvpSumTime'])
            ->setPvpSumKills($aggregator['pvpSumKills'])
            ->setPvpSumDeaths($aggregator['pvpSumDeaths'])
            ->setPvpSumAssists($aggregator['pvpSumAssists'])
            ;

        $this->em->persist($newPantheonDateStat);
        $this->em->flush();
    }

    private function createDefinition()
    {
        return array(
            new InputOption('id', 'i', InputOption::VALUE_REQUIRED, 'id of community to parse'),
            new InputOption('region', 'r', InputOption::VALUE_REQUIRED, 'region of skyforge project'),
        );
    }
}
