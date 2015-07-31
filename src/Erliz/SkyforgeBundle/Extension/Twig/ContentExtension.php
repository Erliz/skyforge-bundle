<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   24.04.2015
 */

namespace Erliz\SkyforgeBundle\Extension\Twig;


use Erliz\SilexCommonBundle\Extension\Twig\ApplicationAwareExtension;
use Erliz\SkyforgeBundle\Entity\Item\ItemRawData;
use Erliz\SkyforgeBundle\Entity\Player;
use Erliz\SkyforgeBundle\Entity\PlayerRoleStat;
use Twig_SimpleFilter;
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
            new Twig_SimpleFunction('proficiency', array($this, 'proficiency')),
            new Twig_SimpleFunction('pveKdr', array($this, 'pveKdr')),
            new Twig_SimpleFunction('pvpKdr', array($this, 'pvpKdr'))
        );
    }

    public function getFilters()
    {
        return array(
            new Twig_SimpleFilter('longestRole', array($this, 'longestActiveRoleStat'))
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

    /**
     * @param Player $player
     *
     * @return PlayerRoleStat
     */
    public function longestActiveRoleStat(Player $player)
    {
        return $this->getApp()['player.skyforge.service']->getLongestActiveRoleStat($player);
    }

    /**
     * @param int $kills
     * @param int $deaths
     *
     * @return float
     */
    public function pveKdr($kills, $deaths)
    {
        return $this->getApp()['player.skyforge.service']->calcPveKdr($kills, $deaths);
    }

    /**
     * @param int $kills
     * @param int $deaths
     * @param int $assists
     *
     * @return float
     */
    public function pvpKdr($kills, $deaths, $assists)
    {
        return $this->getApp()['player.skyforge.service']->calcPvpKdr($kills, $deaths, $assists);
    }
}
