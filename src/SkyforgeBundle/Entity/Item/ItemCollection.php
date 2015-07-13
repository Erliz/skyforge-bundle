<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   15.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity\Item;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

class ItemCollection extends ArrayCollection
{
    /**
     * {@inheritDoc}
     */
    public function add($value)
    {
        if (!($value instanceof Item)) {
            throw new \InvalidArgumentException('Trying add entity into collection not Item entity');
        }
        return parent::add($value);
    }

    /**
     * @param string $name
     *
     * @return Item
     */
    public function findOneByName($name)
    {
        $item = $this->matching(
            Criteria::create()
                ->where(Criteria::expr()->eq('name', $name))
                ->setMaxResults(1)
        );

        return $item->first();
    }
}
