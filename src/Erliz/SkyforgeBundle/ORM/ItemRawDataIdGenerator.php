<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   21.04.2015
 */

namespace Erliz\SkyforgeBundle\ORM;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;

class ItemRawDataIdGenerator extends AbstractIdGenerator
{

    /**
     * Generates an identifier for an entity.
     *
     * @param \Doctrine\ORM\EntityManager  $em
     * @param \Doctrine\ORM\Mapping\Entity $entity
     *
     * @return mixed
     */
    public function generate(EntityManager $em, $entity)
    {
        return $entity->getGeneratedId();
    }
}
