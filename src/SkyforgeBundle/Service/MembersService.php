<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   15.01.2015
 */

namespace Erliz\SkyforgeBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Erliz\SkyforgeBundle\Entity\Player;
use Erliz\SkyforgeBundle\Entity\PlayerDateStat;

class MembersService
{
    /**
     * @param array $players
     *
     * @return float|int
     */
    public function getAvgPrestige($players)
    {
        $sum = 0;
        $count = 0;

        /** @var Player $player */
        foreach ($players as $player) {
            $playerDateStat = $player->getDateStat()->first();
            if (!empty($playerDateStat)) {
                /** @var PlayerDateStat $playerDateStat */
                $sum += $playerDateStat->getMaxPrestige();
                $count++;
            }
        }

        $avg = 0;
        if ($count > 0) {
            $avg = round($sum / $count);
        }

        return $avg;
    }

    /**
     * @param array $players
     *
     * @return float|int
     */
    public function getSumPrestige($players)
    {
        $sum = 0;

        /** @var Player $player */
        foreach ($players as $player) {
            $playerDateStat = $player->getDateStat()->first();
            if (!empty($playerDateStat)) {
                /** @var PlayerDateStat $playerDateStat */
                $sum += $playerDateStat->getMaxPrestige();
            }
        }

        return $sum;
    }

    /**
     * @param array $players
     *
     * @return float|int
     */
    public function getAvgKDR($players)
    {
        $sum = 0;
        $count = 0;

        /** @var Player $player */
        foreach ($players as $player) {
            $roleStat = $player->getLongestActiveRoleStat();
            if (!empty($roleStat)) {
                /** @var PlayerDateStat $playerDateStat */
                $sum += $roleStat->getPvpKdr();
                $count++;
            }
        }

        $avg = 0;
        if ($count > 0) {
            $avg = round($sum / $count, 3);
        }

        return $avg;
    }

    /**
     * @param array $players
     *
     * @return float|int
     */
    public function getTotalPvpKills($players)
    {
        $killsAggregator = 0;
        /** @var Player $player */
        foreach ($players as $player) {
            $killsAggregator += $player->getPvpKills();
        }

        return $killsAggregator;
    }

    /**
     * @param array $players
     *
     * @return float|int
     */
    public function getTotalPvpTime($players)
    {
        $timeAggregator = 0;
        /** @var Player $player */
        foreach ($players as $player) {
            if ($stat = $player->getDateStat()->first()) {
                $timeAggregator += $stat->getPvpTime();
            }
        }

        return $timeAggregator;
    }

    /**
     * @param Collection $players
     *
     * @return ArrayCollection
     */
    public function sortMembersByPrestige(Collection $players)
    {
        $iterator = $players->getIterator();
        $iterator->uasort(function ($a, $b){
            /** @var Player $a */
            /** @var PlayerDateStat $lastDateStatFromA */
            $lastDateStatFromA = $a->getDateStat()->first();
            /** @var Player $b */
            /** @var PlayerDateStat $lastDateStatFromB */
            $lastDateStatFromB = $b->getDateStat()->first();
            if ($lastDateStatFromA && !$lastDateStatFromB) {
                return -1;
            } elseif (!$lastDateStatFromA && $lastDateStatFromB) {
                return 1;
            } elseif (!$lastDateStatFromA && !$lastDateStatFromB) {
                return 0;
            } elseif ($lastDateStatFromA->getMaxPrestige() == $lastDateStatFromB->getMaxPrestige()) {
                return 0;
            } else {
                return ($lastDateStatFromA->getMaxPrestige() < $lastDateStatFromB->getMaxPrestige()) ? 1 : -1;
            }
        });

        return new ArrayCollection(iterator_to_array($iterator));
    }

    /**
     * @param Collection $players
     *
     * @return ArrayCollection
     */
    public function sortMembersByBalance(Collection $players)
    {
        $iterator = $players->getIterator();
        $iterator->uasort(function ($a, $b){
            return $b->getBalance() - $a->getBalance();
        });

        return new ArrayCollection(iterator_to_array($iterator));
    }
}
