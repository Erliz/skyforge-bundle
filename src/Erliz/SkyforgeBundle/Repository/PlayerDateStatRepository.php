<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   27.04.2015
 */

namespace Erliz\SkyforgeBundle\Repository;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class PlayerDateStatRepository extends EntityRepository
{
    /**
     * @param array|null $playersId
     * @param bool|false $asArray
     *
     * @return array
     */
    public function findByPlayerIds(array $playersId, $asArray = false)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $weekAgo = new \DateTime('-7 day');
        $qb->select('pds dateStat', 'IDENTITY(pds.player) playerId')
            ->from('Erliz\SkyforgeBundle\Entity\PlayerDateStat', 'pds')
            ->where($qb->expr()->in('pds.player', ':players_id'))
            ->andWhere($qb->expr()->gt('pds.date', ':date'))
            ->orderBy('pds.date', Criteria::DESC)
            ->setParameter('players_id', $playersId)
            ->setParameter('date', $weekAgo->format("Y-m-d H:i:s"));

        $query = $qb->getQuery()
            ->useResultCache(true)
            ->setResultCacheLifetime(1800);

        return $asArray ? $query->getResult(Query::HYDRATE_ARRAY) : $query->getResult();
    }
}
