<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   15.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity\Item;


use Doctrine\Common\Collections\ArrayCollection;

class ItemTalentCollection extends ArrayCollection
{
    /**
     * {@inheritDoc}
     */
    public function add($value)
    {
        if (!($value instanceof ItemTalent)) {
            throw new \InvalidArgumentException('Trying add entity into collection not ItemTalent entity');
        }
        return parent::add($value);
    }
}
