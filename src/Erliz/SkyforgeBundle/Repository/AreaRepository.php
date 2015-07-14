<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   20.01.2015
 */

namespace Erliz\SkyforgeBundle\Repository;


class AreaRepository extends SimpleAbstractRepository
{

    protected function getItemsName()
    {
        return 'area';
    }

    /**
     * @param $data
     */
    public function fillByData($data)
    {
        $library = $this->getLibrary();
        foreach ($data as $area) {
            $library[(string)$area->resourceId] = $area;
        }
        $this->setLibrary($library);
    }
}