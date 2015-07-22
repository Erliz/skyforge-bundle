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
            $this->getEntityManager()->createQuery($dql)->setMaxResults(1000)->getResult(Query::HYDRATE_ARRAY)
        );
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
