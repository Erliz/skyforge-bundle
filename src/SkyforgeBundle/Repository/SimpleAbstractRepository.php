<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   20.01.2015
 */

namespace Erliz\SkyforgeBundle\Repository;


abstract class SimpleAbstractRepository
{
    private $library = array();

    public function getById($id)
    {
        if(!empty($this->library[$id])){
            return $this->library[$id];
        }
        throw new \InvalidArgumentException(sprintf('Not found %s with id %s', $this->getItemsName(),$id));
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->getLibrary();
    }

    /**
     * @param $data
     */
    public function fillByData($data)
    {
        $library = $this->getLibrary();
        foreach ($data as $area) {
            $library[(string)$area->id] = $area;
        }
        $this->setLibrary($library);
    }

    /**
     * @return array
     */
    protected function getLibrary()
    {
        return $this->library;
    }

    /**
     * @param array $library
     */
    protected function setLibrary(array $library)
    {
        $this->library = $library;
    }

    abstract protected function getItemsName();
}
