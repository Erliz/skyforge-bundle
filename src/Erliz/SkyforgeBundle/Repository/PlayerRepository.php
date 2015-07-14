<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   27.04.2015
 */

namespace Erliz\SkyforgeBundle\Repository;


use Doctrine\ORM\EntityRepository;
use Erliz\SkyforgeBundle\Entity\CommunityInterface;

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
}
