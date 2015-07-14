<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 02.04.2015
 */

namespace Erliz\SkyforgeBundle\Service;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Erliz\SilexCommonBundle\Service\ApplicationAwareService;
use Erliz\SkyforgeBundle\Entity\Player;
use Erliz\SkyforgeBundle\Entity\PlayerDateStat;
use Erliz\SkyforgeBundle\Entity\PlayerRoleStat;
use Monolog\Logger;
use RuntimeException;
use Silex\Application;

class StatService extends ApplicationAwareService
{
    const AIR_TYPE = "RULE_TYPE_AIR";
    const DIMENSION_TYPE = "RULE_TYPE_DIMENSION";
    const GROUP_TYPE = "RULE_TYPE_GROUP";
    const PVE_TYPE = "RULE_TYPE_PVE";
    const PVP_TYPE = "RULE_TYPE_PVP";
    const SOLO_TYPE = "RULE_TYPE_SOLO";

    /** @var EntityRepository $dateStatRepo */
    private $dateStatRepo;
    /** @var EntityRepository $dateRoleRepo */
    private $roleStatRepo;
    /** @var EntityRepository $pantheonRepo */
    private $pantheonRepo;
    /** @var EntityRepository $roleRepo */
    private $roleRepo;
    /** @var EntityRepository $playerRepo */
    private $playerRepo;
    /** @var Logger */
    private $logger;

    public function __construct(Application $application)
    {
        parent::__construct($application);

        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        $this->logger = $this->getLogger();

        $this->dateStatRepo = $em->getRepository('Erliz\SkyforgeBundle\Entity\PlayerDateStat');
        $this->roleStatRepo = $em->getRepository('Erliz\SkyforgeBundle\Entity\PlayerRoleStat');
        $this->pantheonRepo = $em->getRepository('Erliz\SkyforgeBundle\Entity\Pantheon');
        $this->roleRepo = $em->getRepository('Erliz\SkyforgeBundle\Entity\Role');
        $this->playerRepo = $em->getRepository('Erliz\SkyforgeBundle\Entity\Player');
    }

    /**
     * @param $playerId
     */
    public function updatePlayer($playerId)
    {
        $app = $this->getApp();
        /** @var EntityManager $em */
        $em = $this->getEntityManager();
        /** @var ParseService $parseService */
        $parseService = $app['parse.skyforge.service'];
        $parseService->setAuthData($app['config']['skyforge']['statistic']);

        /** @var Player $player */
        $player = $this->playerRepo->find($playerId);
        try {
            $avatarDataTimeStart = microtime(true);
            $avatarData = $parseService->getDataFromProfilePage($playerId);
            $this->logger->addInfo(sprintf('Avatar data take %s sec to proceed', microtime(true) - $avatarDataTimeStart));

            $statDataTimeStart = microtime(true);
            $statData = $parseService->getPlayerStat($playerId);
            $this->logger->addInfo(sprintf('Stat data take %s sec to proceed', microtime(true) - $statDataTimeStart));
        } catch (RuntimeException $e) {
            if ($e->getCode() == 403) {
                throw new RuntimeException(sprintf('Stat data are closed for player "%s"', $playerId), 403);
            } else {
                throw $e;
            }
        }

        if (!$player) {
            $player = new Player();
            $player->setId($playerId);
            $em->persist($player);
        }

        $currentPantheon = null;
        if ($avatarData && $avatarData['pantheon_id']) {
            $currentPantheon = $this->pantheonRepo->find($avatarData['pantheon_id']);
        }
        $currentRole = $this->roleRepo->findOneBy(array('name' => $avatarData['role_name']));
        if (empty($currentRole)) {
            $currentRole = $this->roleRepo->findOneBy(array('name' => 'Храмовник'));
        }
        $today = new \DateTime('-4 hour');
        $shownDates = $statData->avatarStats->daysToShow;
        $dailyStatsLoopTimeStart = microtime(true);
        foreach ($statData->avatarStats->dailyStats as $key => $dayStat) {
            // -4 hour server update on 4 hour at night
            $date = new \DateTime(($key - $shownDates + 1) . ' day -4 hour');

            $totalTime = null;
            if ($key + 1 == $shownDates) {
                $totalTime = $statData->avatarStats->secondsPlayed;
            }

            if ($oldDateStat = $this->dateStatRepo->findOneBy(array('player' => $player->getId(), 'date' => $date))) {
                if($today->format('Y-m-d') == $oldDateStat->getDate()->format('Y-m-d')) {
                    $oldDateStat->setCurrentPrestige($avatarData['prestige']['current'])
                                ->setMaxPrestige($avatarData['prestige']['max'])
                                ->setRole($currentRole)
                                ->setPantheon($currentPantheon)
                                ->setTotalTime($totalTime);
                }
                $oldDateStat->setPveMobKills($dayStat->pveMobKills)
                            ->setPveBossKills($dayStat->pveBossKills)
                            ->setPveDeaths($dayStat->pveDeaths)
                            ->setPvpKills($dayStat->pvpKills)
                            ->setPvpDeaths($dayStat->pvpDeaths)
                            ->setPvpAssists($dayStat->pvpAssists);
                continue;
            }

            $player->setName($avatarData['name'])
                   ->setNick($avatarData['nick'])
                   ->setImg($avatarData['img'])
                   ->setCurrentRole($currentRole);

            $dateStat = new PlayerDateStat();
            $dateStat->setPlayer($player)
                     ->setDate($date)
                     ->setRole($currentRole)
                     ->setPantheon($currentPantheon)
                     ->setTotalTime($totalTime)
//                     ->setSoloTime($this->getTimeSpentByAdventureType($statData->adventureStats->byAdventureStats, $this::SOLO_TYPE))
                     ->setPvpTime($this->getTimeSpentByAdventureType($statData->adventureStats->byAdventureStats, $this::PVP_TYPE))
                     ->setCurrentPrestige($avatarData['prestige']['current'])
                     ->setMaxPrestige($avatarData['prestige']['max'])
                     ->setPveMobKills($dayStat->pveMobKills)
                     ->setPveBossKills($dayStat->pveBossKills)
                     ->setPveDeaths($dayStat->pveDeaths)
                     ->setPvpKills($dayStat->pvpKills)
                     ->setPvpDeaths($dayStat->pvpDeaths)
                     ->setPvpAssists($dayStat->pvpAssists);

            $em->persist($dateStat);
        }
        $this->logger->addInfo(sprintf('Daily stat take %s sec to proceed', microtime(true) - $dailyStatsLoopTimeStart));

        $classStatsLoopTimeStart = microtime(true);
        foreach ($statData->avatarStats->classStats as $roleData) {
            $role = $this->roleRepo->findOneBy(array('resourceId' => $roleData->characterClass->resourceId));
            if (!$role) {
                throw new RuntimeException('New role detected ' . json_encode($roleData->characterClass));
            }
            $roleStat = $this->roleStatRepo->findOneBy(array('player' => $player->getId(), 'role' => $role->getId()));
            if (!$roleStat) {
                $roleStat = new PlayerRoleStat();
                $roleStat->setPlayer($player)
                         ->setRole($role);
                $em->persist($roleStat);
            }
            $roleDataStat = $roleData->stats;
            $roleStat
                ->setSecondsActivePlayed($roleData->secondsActivePlayed)
                ->setSecondsPlayed($roleData->secondsPlayed)
                ->setPveMobKills($roleDataStat->pveMobKills)
                ->setPveBossKills($roleDataStat->pveBossKills)
                ->setPveDeaths($roleDataStat->pveDeaths)
                ->setPvpKills($roleDataStat->pvpKills)
                ->setPvpDeaths($roleDataStat->pvpDeaths)
                ->setPvpAssists($roleDataStat->pvpAssists);
        }
        $this->logger->addInfo(sprintf('Class stat take %s sec to proceed', microtime(true) - $classStatsLoopTimeStart));

        $flushTimeStart = microtime(true);
        $em->flush();
        $this->logger->addInfo(sprintf('Flush db take %s sec to proceed', microtime(true) - $flushTimeStart ));
    }

    /**
     * @param array $adventureStats
     * @param $adventureType
     * @return int
     */
    public function getTimeSpentByAdventureType(array $adventureStats, $adventureType)
    {
        $timeAggregator = 0;
        foreach ($adventureStats as $adventure) {
            if(in_array($adventureType, $adventure->rule->types)){
                $timeAggregator += round($adventure->timeSpent / 1000);
            }
        }

        return $timeAggregator;
    }
}