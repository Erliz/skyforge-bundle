<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   31.03.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use Doctrine\Common\Collections\ArrayCollection;

class PlayerCollection extends ArrayCollection
{
    /**
     * {@inheritDoc}
     */
    public function add($value)
    {
        if (!($value instanceof Player)) {
            throw new \InvalidArgumentException('Trying add entity into collection not PantheonEntity');
        }
        return parent::add($value);
    }
}
