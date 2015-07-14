<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   15.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;

class ParamCollection extends ArrayCollection
{
    /**
     * {@inheritDoc}
     */
    public function add($value)
    {
        if (!($value instanceof Param)) {
            throw new \InvalidArgumentException('Trying add entity into collection not Param entity');
        }
        return parent::add($value);
    }
}