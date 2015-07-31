<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 15.07.2015
 */

namespace Erliz\SkyforgeBundle\Service;


use Doctrine\ORM\Query;
use Erliz\SilexCommonBundle\Service\ApplicationAwareService;
use Erliz\SkyforgeBundle\Entity\Player;
use Erliz\SkyforgeBundle\Entity\PlayerCollection;
use Erliz\SkyforgeBundle\Entity\PlayerDateStat;
use Erliz\SkyforgeBundle\Entity\PlayerRoleStat;
use Silex\Application;

class PlayerService extends ApplicationAwareService
{
    const SLACK_PRESTIGE_COUNT = 500;

    /**
     * @return PlayerCollection
     */
    public function getTop()
    {
        $dql = '
            select
                p,
                pt,
                pds,
                max(pds.maxPrestige) maxPrestige
            from Erliz\SkyforgeBundle\Entity\Player p
                 join p.dateStat pds
                 left join p.pantheon pt
            where
                 p.name is not null
            group by pds.player
            order by maxPrestige DESC
        ';

        return new PlayerCollection(
            $this->getEntityManager()
                ->createQuery($dql)
                ->useResultCache(true)
                ->setResultCacheLifetime(300)
                ->setMaxResults(1000)
                ->getResult(Query::HYDRATE_ARRAY)
        );
    }

    /**
     * @param Player $player
     *
     * @return PlayerRoleStat
     */
    public function getLongestActiveRoleStat(Player $player)
    {
        $roleStats = $player->getRoleStat();

        /** @var PlayerRoleStat $roleStat */
        $roleStat = null;
        foreach ($roleStats as $stat) {
            if (is_null($roleStat) || $roleStat->getSecondsActivePlayed() < $stat->getSecondsActivePlayed()) {
                $roleStat = $stat;
            }
        }

        return $roleStat;
    }

    /**
     * @param int $kills
     * @param int $deaths
     *
     * @return float
     */
    public function calcPveKdr($kills, $deaths)
    {
        return $deaths ? round($kills / $deaths, 2) : 0;
    }

    /**
     * @param int $kills
     * @param int $deaths
     * @param int $assists
     *
     * @return float
     */
    public function calcPvpKdr($kills, $deaths, $assists)
    {
        return $deaths ? round(($kills + $assists * 0.25) / $deaths, 2) : 0;
    }

    /**
     * @param array $players
     *
     * @return float|int
     */
    public function getSlackPlayersCount($players)
    {
        $count = 0;
        /** @var Player $player */
        foreach ($players as $player) {
            /** @var PlayerDateStat $statWeek */
            if ($statWeek = $player->getDateStat()->get(7)) {
                /** @var PlayerDateStat $statLast */
                if ($statLast = $player->getDateStat()->first()) {
                    if (($statLast->getMaxPrestige() - $statWeek->getMaxPrestige()) <= $this::SLACK_PRESTIGE_COUNT) {
                        $count++;
                    }
                }
            }
        }

        return $count;
    }
}
