<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   20.01.2015
 */

namespace Erliz\SkyforgeBundle\Repository;


class MissionRepository extends SimpleAbstractRepository
{

    protected function getItemsName()
    {
        return 'mission';
    }
}