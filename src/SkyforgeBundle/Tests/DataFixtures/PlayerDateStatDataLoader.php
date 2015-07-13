<?php

/**
 * @author Stanislav Vetlovskiy
 * @date   21.11.2014
 */

namespace Erliz\SkyforgeBundle\Tests\DataFixtures;


use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Erliz\SkyforgeBundle\Entity\PlayerDateStat;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class PlayerDateStatLoader implements FixtureInterface//, DependentFixtureInterface
{
    public function getDependencies()
    {
        return array(
            'Erliz\SkyforgeBundle\Tests\DataFixtures\PlayerDataLoader'
        );
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {

        $dataFile = __DIR__ . '/dump/PlayerDateStat.yml';
        if (!file_exists($dataFile)) {
            throw new RuntimeException(sprintf('No file exist with fixture data on "%s" path', $dataFile));
        }
        $dataList = Yaml::parse($dataFile);

        $pantheonRepo = $manager->getRepository('Erliz\SkyforgeBundle\Entity\Pantheon');
        $pantheonsArray = $pantheonRepo->findAll();
        $pantheons = array();
        foreach ($pantheonsArray as $pantheon) {
            $pantheons[$pantheon->getId()] = $pantheon;
        }
        unset($pantheonsArray);

        $playerRepo = $manager->getRepository('Erliz\SkyforgeBundle\Entity\Player');
        $playersArray = $playerRepo->findAll();
        $players = array();
        foreach ($playersArray as $player) {
            $players[$player->getId()] = $player;
        }
        unset($playersArray);

        $roleRepo = $manager->getRepository('Erliz\SkyforgeBundle\Entity\Role');
        $roleArray = $roleRepo->findAll();
        $roles = array();
        foreach ($roleArray as $role) {
            $roles[$role->getName()] = $role;
        }
        unset($roleArray);

        foreach ($dataList as $item) {
            $playerDateStat = new PlayerDateStat();
            $playerDateStat
                ->setPlayer($players[$item['player']])
                ->setDate(new \DateTime($item['date']))
                ->setRole($roles[$item['role']])
                ->setMaxPrestige($item['max_prestige'])
                ->setCurrentPrestige($item['current_prestige'])
                ->setTotalTime($item['total_time'])
                ->setPveBossKills($item['pve_boss_kills'])
                ->setPveMobKills($item['pve_mob_kills'])
                ->setPveDeaths($item['pve_deaths'])
                ->setPvpKills($item['pvp_kills'])
                ->setPvpDeaths($item['pvp_deaths'])
                ->setPvpAssists($item['pvp_assists']);

            if (!empty($item['pantheon'])) {
                $playerDateStat->setPantheon($pantheons[$item['pantheon']]);
            }

            $manager->persist($playerDateStat);
            $manager->flush();
        }
    }
}