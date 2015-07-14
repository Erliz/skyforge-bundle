<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   24.04.2015
 */

namespace Erliz\SkyforgeBundle\Service;


use Erliz\SilexCommonBundle\Service\ApplicationAwareService;
use Erliz\SkyforgeBundle\Entity\Item\ItemQuality;

class ItemService extends ApplicationAwareService
{
    public function getQualityMap()
    {
        $qualityMap = array();

        /** @var ItemQuality $quality */
        foreach ($this->getQualityCollection() as $quality) {
            if(empty($qualityMap[$quality->getLevel()])) {
                $qualityMap[$quality->getLevel()] = array();
            }
            $qualityMap[$quality->getLevel()][$quality->getQuality()] = $quality->getProficiency();
        }

        return $qualityMap;
    }

    private function getQualityCollection()
    {
        return $this->getEntityManager()->getRepository('Erliz\SkyforgeBundle\Entity\Item\ItemQuality')->findAll();
    }
}