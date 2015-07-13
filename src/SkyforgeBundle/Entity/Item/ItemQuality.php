<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   24.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity\Item;


use Doctrine\ORM\Mapping as ORM;

/**
 * ItemQuality.
 *
 * @ORM\Table(name="item_quality", uniqueConstraints={@Orm\UniqueConstraint(columns={"level", "quality"})}))
 * @ORM\Entity
 */
class ItemQuality
{
    const UNCOMMON = 'uncommon';
    const COMMON = 'common';
    const RARE = 'rare';
    const EPIC = 'epic';
    const LEGENDARY = 'legendary';


    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer")
     */
    private $level;

    /**
     * @var string
     *
     * @ORM\Column(name="quality", type="string", length=12)
     */
    private $quality;

    /**
     * @var int
     *
     * @ORM\Column(name="proficiency", type="integer")
     */
    private $proficiency;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     *
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return string
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @param string $quality
     *
     * @return $this
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;

        return $this;
    }

    /**
     * @return int
     */
    public function getProficiency()
    {
        return $this->proficiency;
    }

    /**
     * @param int $proficiency
     *
     * @return $this
     */
    public function setProficiency($proficiency)
    {
        $this->proficiency = $proficiency;

        return $this;
    }

    /**
     * @param string $quality - constant from self
     *
     * @return mixed
     */
    static public function getUserFriendlyName($quality)
    {
        return self::getQualityMap()[$quality];
    }

    static public function getQualityMap()
    {
        return array(
            self::COMMON    => 'Белый',
            self::UNCOMMON  => 'Зеленый',
            self::RARE      => 'Синий',
            self::EPIC      => 'Фиолетовый',
            self::LEGENDARY => 'Оранжевый'
        );
    }
}