<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   27.04.2015
 */

namespace Erliz\SkyforgeBundle\Repository;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Erliz\SkyforgeBundle\Entity\CommunityInterface;
use Erliz\SkyforgeBundle\Entity\Pantheon;
use Erliz\SkyforgeBundle\Entity\PlayerCollection;

class PlayerRepository extends EntityRepository
{
    /**
     * @param CommunityInterface $community
     *
     * @return array
     */
    public function findByCommunity(CommunityInterface $community)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('p')
            ->from('Erliz\SkyforgeBundle\Entity\Player', 'p')
            ->leftJoin('Erliz\SkyforgeBundle\Entity\Community', 'c')
            ->where($qb->expr()->eq('c.id', ':community_id'))
            ->setParameter('community_id', $community->getId());

        return $qb->getQuery()->getResult();
    }

    /**
     * @param int  $pantheonId
     * @param bool $asArray
     *
     * @return array
     */
    public function findByPantheonId($pantheonId, $asArray = false)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('p')
            ->from('Erliz\SkyforgeBundle\Entity\Player', 'p')
            ->join('p.pantheon', 'pt')
            ->where($qb->expr()->eq('pt.id', ':pantheon_id'))
            ->setParameter('pantheon_id', $pantheonId);

        $query = $qb->getQuery()
            ->useResultCache(true)
            ->setResultCacheLifetime(1800);

        return $asArray ? $query->getResult(Query::HYDRATE_ARRAY) : $query->getResult();
    }

    /**
     * @param Pantheon $pantheon
     *
     * @return ArrayCollection
     */
    public function getDateStatByPantheon($pantheon)
    {
        $params = array(':pantheonId' => $pantheon->getId());
        $qb = $this->getEntityManager()->createQueryBuilder();

        return new ArrayCollection(
            $qb->select('pds')
                ->from('Erliz\SkyforgeBundle\Entity\PlayerDateStat', 'pds')
                ->leftJoin('pds.player', 'p')
                ->where($qb->expr()->eq('p.pantheon', ':pantheonId'))
                ->orderBy('pds.date', Criteria::DESC)
            ->getQuery()
            ->setParameters($params)
            ->useResultCache(true)
            ->setResultCacheLifetime(1800)
            ->getResult()
        );
    }

    /**
     * @param Pantheon $pantheon
     *
     * @return ArrayCollection
     */
    public function getRoleStatByPantheon($pantheon)
    {
        $params = array(':pantheonId' => $pantheon->getId());
        $qb = $this->getEntityManager()->createQueryBuilder();

        return new ArrayCollection(
            $qb->select('prs')
                ->from('Erliz\SkyforgeBundle\Entity\PlayerRoleStat', 'prs')
                ->leftJoin('prs.player', 'p')
                ->where($qb->expr()->eq('p.pantheon', ':pantheonId'))
                ->orderBy('prs.secondsActivePlayed', Criteria::DESC)
            ->getQuery()
            ->setParameters($params)
            ->useResultCache(true)
            ->setResultCacheLifetime(1800)
            ->getResult()
        );
    }

    public function findWithDateStatByPantheon(Pantheon $pantheon)
    {
        $dql = '
            select
                p.*,
                pds.*
            from Erliz\SkyforgeBundle\Entity\Player p
                left join (select player, MAX(max_prestige) max_prestige from Erliz\SkyforgeBundle\Entity\PlayerDateStat group by player) m_pds
                left join Erliz\SkyforgeBundle\Entity\PlayerDateStat pds on pds.max_prestige = m_pds.max_prestige and pds.player = p.id
            where p.pantheon = :pantheonId
            group by p.id
            order by pds.max_prestige DESC
        ';

        $params = array(':pantheonId' => $pantheon->getId());

        return new PlayerCollection(
            $this->getEntityManager()
                ->createQuery($dql)
                ->setParameters($params)
                ->useResultCache(true)
                ->setResultCacheLifetime(1800)
                ->getResult(Query::HYDRATE_ARRAY)
        );
    }
}
