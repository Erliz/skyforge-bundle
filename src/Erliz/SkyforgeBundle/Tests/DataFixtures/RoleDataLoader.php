<?php

/**
 * @author Stanislav Vetlovskiy
 * @date   21.11.2014
 */

namespace Erliz\SkyforgeBundle\Tests\DataFixtures;


use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Erliz\SkyforgeBundle\Entity\Role;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class RoleDataLoader implements FixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {
        return;
        $dataFile = __DIR__ . '/dump/Role.yml';
        if (!file_exists($dataFile)) {
            throw new RuntimeException(sprintf('No file exist with fixture data on "%s" path', $dataFile));
        }
        $dataList = Yaml::parse($dataFile);

        foreach ($dataList as $item) {
            $role = new Role();
            $role->setName($item['name'])
                 ->setImg($item['img'])
                 ->setResourceId($item['resource_id']);

            $manager->persist($role);
        }

        $manager->flush();
    }
}