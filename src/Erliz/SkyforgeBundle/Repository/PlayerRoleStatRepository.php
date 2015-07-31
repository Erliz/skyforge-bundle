<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   27.04.2015
 */

namespace Erliz\SkyforgeBundle\Repository;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

class PlayerRoleStatRepository extends EntityRepository
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

        $qb->select('prs roleStat', 'IDENTITY(prs.player) playerId',  'IDENTITY(prs.role) roleId')
            ->from('Erliz\SkyforgeBundle\Entity\PlayerRoleStat', 'prs')
            ->where($qb->expr()->in('prs.player', ':players_id'))
            ->orderBy('prs.secondsActivePlayed', Criteria::DESC)
            ->setParameter('players_id', $playersId);

        $query = $qb->getQuery()
            ->useResultCache(true)
            ->setResultCacheLifetime(1800);

        return $asArray ? $query->getResult(Query::HYDRATE_ARRAY) : $query->getResult();
    }
}
