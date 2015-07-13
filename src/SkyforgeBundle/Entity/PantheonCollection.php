<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   31.03.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;

class PantheonCollection extends ArrayCollection
{
    /**
     * {@inheritDoc}
     */
    public function add($value)
    {
        if (!($value instanceof Pantheon)) {
            throw new \InvalidArgumentException('Trying add entity into collection not Pantheon entity');
        }
        return parent::add($value);
    }
}
