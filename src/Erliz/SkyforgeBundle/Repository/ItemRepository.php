<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   20.01.2015
 */

namespace Erliz\SkyforgeBundle\Repository;


class ItemRepository extends SimpleAbstractRepository
{

    protected function getItemsName()
    {
        return 'item';
    }
}
