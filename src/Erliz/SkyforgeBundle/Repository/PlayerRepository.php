<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   27.04.2015
 */

namespace Erliz\SkyforgeBundle\Repository;


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

    public function findWithDateStatByPantheon(Pantheon $pantheon)
    {
        $dql = '
            select
                p,
                pds
            from Erliz\SkyforgeBundle\Entity\Player p
                 join p.dateStat pds
            where
                p.pantheon = :pantheonId
            order by pds.maxPrestige DESC, pds.date DESC
        ';
        $params = array(':pantheonId' => $pantheon->getId());

        return new PlayerCollection(
            $this->getEntityManager()
                ->createQuery($dql)
                ->setParameters($params)
                ->useResultCache(true)
                ->setResultCacheLifetime(300)
                ->getResult()
        );
    }
}
