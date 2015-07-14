<?php

/**
 * @author Stanislav Vetlovskiy
 * @date   21.11.2014
 */

namespace Erliz\SkyforgeBundle\Tests\DataFixtures;


use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Erliz\SkyforgeBundle\Entity\Community;
use Erliz\SkyforgeBundle\Entity\Player;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

class CommunityDataLoader implements FixtureInterface, DependentFixtureInterface
{
    public function getDependencies()
    {
        return array('Erliz\SkyforgeBundle\Tests\DataFixtures\PlayerDataLoader');
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    function load(ObjectManager $manager)
    {
        return;
        $dataFile = __DIR__ . '/dump/Community.yml';
        if (!file_exists($dataFile)) {
            throw new RuntimeException(sprintf('No file exist with fixture data on "%s" path', $dataFile));
        }
        $dataList = Yaml::parse($dataFile);
        $playerRepo = $manager->getRepository('Erliz\SkyforgeBundle\Entity\Player');

        foreach ($dataList as $item) {
            $community = new Community();
            $community->setId($item['id'])
                      ->setName($item['name'])
                      ->setUpdatedAt(new \DateTime($item['updated_at']));

            if (!empty($item['img'])) {
                $community->setImg($item['img']);
            }

            foreach ($item['members'] as $memberId) {
                /** @var Player $player */
                $player = $playerRepo->find($memberId);
                $player->getCommunities()->add($community);
            }

            $manager->persist($community);
        }
        $manager->flush();
    }
}