<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   24.04.2015
 */

namespace Erliz\SkyforgeBundle\Extension\Twig;


use Erliz\SilexCommonBundle\Extension\Twig\ApplicationAwareExtension;
use Erliz\SkyforgeBundle\Entity\Item\ItemRawData;
use Twig_SimpleFunction;

class ContentExtension extends ApplicationAwareExtension
{
    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'Content';
    }

    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('proficiency', array($this, 'proficiency'))
        );
    }

    /**
     * @param ItemRawData $item
     *
     * @return string
     */
    public function proficiency(ItemRawData $item)
    {
        $map = $this->getApp()['item.skyforge.service']->getQualityMap();
        if (!empty($map[$item->getLevel()]) && !empty($map[$item->getLevel()][$item->getQuality()])) {
                $proficiency = $map[$item->getLevel()][$item->getQuality()];
        } else {
            $proficiency = $item->getLevel() . ' lvl';
        }

        return $proficiency;
    }
}
