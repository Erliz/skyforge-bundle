<?php

/**
 * @author Stanislav Vetlovskiy
 * @date 15.07.2015
 */

namespace Erliz\SkyforgeBundle\Service;


use Doctrine\ORM\Query;
use Erliz\SilexCommonBundle\Service\ApplicationAwareService;
use Erliz\SkyforgeBundle\Entity\PlayerCollection;
use Silex\Application;

class PlayerService extends ApplicationAwareService
{

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
                 join p.pantheon pt
            where
                 p.name is not null
            group by pds.player
            order by maxPrestige DESC
        ';

        return new PlayerCollection(
            $this->getEntityManager()->createQuery($dql)->setMaxResults(1000)->getResult(Query::HYDRATE_ARRAY)
        );
    }
}
