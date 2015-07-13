<?php

/**
 * @author Stanislav Vetlovskiy
 * @date   21.11.2014
 */

namespace Erliz\SkyforgeBundle\Tests\DataFixtures;


use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Erliz\SkyforgeBundle\Entity\PlayerRoleStat;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class PlayerRoleStatLoader implements FixtureInterface//, DependentFixtureInterface
{
    public function getDependencies()
    {
        return array(
            'Erliz\SkyforgeBundle\Tests\DataFixtures\PlayerDataLoader',
        );
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {
        return;
        $dataFile = __DIR__ . '/dump/PlayerRoleStat.yml';
        if (!file_exists($dataFile)) {
            throw new RuntimeException(sprintf('No file exist with fixture data on "%s" path', $dataFile));
        }
        $dataList = Yaml::parse($dataFile);

        $playerRepo = $manager->getRepository('Erliz\SkyforgeBundle\Entity\Player');
        $roleRepo = $manager->getRepository('Erliz\SkyforgeBundle\Entity\Role');

        foreach ($dataList as $item) {
            $playerRoleStat = new PlayerRoleStat();

            $playerRoleStat
                ->setPlayer($playerRepo->find($item['player']))
                ->setRole($roleRepo->findOneBy(array('name' => $item['role'])))
                ->setSecondsPlayed($item['seconds_played'])
                ->setSecondsActivePlayed($item['seconds_active_played'])
                ->setPveBossKills($item['pve_boss_kills'])
                ->setPveMobKills($item['pve_mob_kills'])
                ->setPveDeaths($item['pve_deaths'])
                ->setPvpKills($item['pvp_kills'])
                ->setPvpDeaths($item['pvp_deaths'])
                ->setPvpAssists($item['pvp_assists'])
                ->setModifiedAt(new \DateTime($item['modified_at']));

            $manager->persist($playerRoleStat);
            $manager->flush();
        }
    }
}