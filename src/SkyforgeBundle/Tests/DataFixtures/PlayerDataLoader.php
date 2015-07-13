<?php

/**
 * @author Stanislav Vetlovskiy
 * @date   21.11.2014
 */

namespace Erliz\SkyforgeBundle\Tests\DataFixtures;


use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Erliz\SkyforgeBundle\Entity\Player;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class PlayerDataLoader implements FixtureInterface, DependentFixtureInterface
{
    public function getDependencies()
    {
        return array(
            'Erliz\SkyforgeBundle\Tests\DataFixtures\PantheonDataLoader',
            'Erliz\SkyforgeBundle\Tests\DataFixtures\RoleDataLoader'
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
        $dataFile = __DIR__ . '/dump/Player.yml';
        if (!file_exists($dataFile)) {
            throw new RuntimeException(sprintf('No file exist with fixture data on "%s" path', $dataFile));
        }
        $dataList = Yaml::parse($dataFile);

        $pantheonRepo = $manager->getRepository('Erliz\SkyforgeBundle\Entity\Pantheon');
        $roleRepo = $manager->getRepository('Erliz\SkyforgeBundle\Entity\Role');
        $persistCounter = 0;
        foreach ($dataList as $item) {
            $player = new Player();
            $player->setId($item['id'])
                   ->setName($item['nick'])
                   ->setNick($item['name'])
                   ->setImg($item['img'])
                   ->setCreatedAt(new \DateTime($item['created_at']))
                   ->setModifiedAt(new \DateTime($item['modified_at']));

            if (!empty($item['pantheon'])) {
                $player->setPantheon($pantheonRepo->findOneBy(array('name' => $item['pantheon'])));
            }
            if (!empty($item['current_role'])) {
                $player->setCurrentRole($roleRepo->findOneBy(array('name' => $item['current_role'])));
            }
            $manager->persist($player);
            $persistCounter++;
            if ($persistCounter > 1000) {
                $manager->flush();
                $persistCounter = 0;
            }
        }
        $manager->flush();
    }
}