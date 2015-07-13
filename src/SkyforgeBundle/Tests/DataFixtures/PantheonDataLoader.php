<?php

/**
 * @author Stanislav Vetlovskiy
 * @date   21.11.2014
 */

namespace Erliz\SkyforgeBundle\Tests\DataFixtures;


use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Erliz\SkyforgeBundle\Entity\Pantheon;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class PantheonDataLoader implements FixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {
        return;
        $dataFile = __DIR__ . '/dump/Pantheon.yml';
        if (!file_exists($dataFile)) {
            throw new RuntimeException(sprintf('No file exist with fixture data on "%s" path', $dataFile));
        }
        $dataList = Yaml::parse($dataFile);

        foreach ($dataList as $item) {
            $pantheon = new Pantheon();
            $pantheon->setId($item['id'])
                     ->setName($item['name'])
                     ->setUpdatedAt(new \DateTime($item['updated_at']));

            if (!empty($item['img'])) {
                $pantheon->setImg($item['img']);
            }

            $manager->persist($pantheon);
        }
        $manager->flush();
    }
}