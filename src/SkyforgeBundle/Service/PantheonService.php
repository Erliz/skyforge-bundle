<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   31.03.2015
 */

namespace Erliz\SkyforgeBundle\Service;


use Doctrine\ORM\EntityRepository;
use Erliz\SkyforgeBundle\Entity\PantheonCollection;
use Erliz\SkyforgeBundle\Entity\Pantheon;

class PantheonService
{
    /** @var EntityRepository */
    private $repository;

    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
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
}
