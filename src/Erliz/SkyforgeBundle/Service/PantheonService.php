<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   31.03.2015
 */

namespace Erliz\SkyforgeBundle\Service;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Erliz\SilexCommonBundle\Service\ApplicationAwareService;
use Erliz\SkyforgeBundle\Entity\PantheonCollection;
use Erliz\SkyforgeBundle\Entity\Pantheon;
use Silex\Application;

class PantheonService extends ApplicationAwareService
{
    /** @var EntityRepository */
    private $repository;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->repository = $this->getEntityManager()->getRepository('Erliz\SkyforgeBundle\Entity\Pantheon');
    }

    /**
     * @return PantheonCollection
     */
    public function findAll()
    {
        return $this->repository->findAll();
    }

    /**
     * @param int $id
     *
     * @return bool|Pantheon
     */
    public function getById($id)
    {
        $pantheon = $this->repository->find($id);
        if (!$pantheon) {
            throw new \InvalidArgumentException(sprintf('Pantheon with id "%s" not found', $id));
        }

        return $pantheon;
    }

    public function getTop()
    {
        $dql = '
            select
                pds,
                pt,
                max(pds.date)
            from Erliz\SkyforgeBundle\Entity\PantheonDateStat pds
                join pds.pantheon pt
            where
                pds.sumPrestige > 0
            group by pds.pantheon
            order by pds.sumPrestige DESC
        ';

        return new ArrayCollection(
            $this->getEntityManager()->createQuery($dql)->setMaxResults(100)->getResult()
        );
    }
}
