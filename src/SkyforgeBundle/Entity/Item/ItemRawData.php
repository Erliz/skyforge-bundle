<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   15.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity\Item;

use Doctrine\ORM\Mapping as ORM;
use Erliz\SkyforgeBundle\Entity\ParamCollection;
use Erliz\SkyforgeBundle\Entity\Role;


/**
 * Item
 *
 * @ORM\Table(name="item_raw_data")
 * @ORM\Entity
 */
class ItemRawData
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="string", length=32)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Erliz\SkyforgeBundle\ORM\ItemRawDataIdGenerator")
     */
    private $id;

    /**
     * @var Item
     *
     * @ORM\ManyToOne(targetEntity="Item", inversedBy="talents")
     * @ORM\JoinColumn(name="item", referencedColumnName="id", nullable=false)
     */
    private $item;

    /**
     * @var int
     *
     * @ORM\Column(name="prestige", type="integer", nullable=false)
     */
    private $prestige;

    /**
     * @var string
     *
     * @ORM\Column(name="player", type="bigint", nullable=false)
     */
    private $player;

    /**
     * @var int
     *
     * @ORM\Column(name="level", type="integer", nullable=false)
     */
    private $level;

    /**
     * @var string
     *
     * @ORM\Column(name="quality", type="string", type="string", length=10, nullable=false)
     */
    private $quality;

    /**
     * @var string
     *
     * @ORM\Column(name="resource_id", type="string", length=20, nullable=false)
     */
    private $resourceId;

    /**
     * @var string
     *
     * @ORM\Column(name="talents_string", type="string", length=1024, nullable=true)
     */
    private $talentsString;

    /**
     * @var string
     *
     * @ORM\Column(name="properties_string", type="string", length=255, nullable=true)
     */
    private $propertiesString;

    public function getGeneratedId()
    {
        $hash = md5(
            $this->getItem()->getId() .
            $this->getLevel() .
            $this->getPrestige() .
            $this->getQuality() .
            $this->getResourceId() .
            $this->getPropertiesString() .
            $this->getTalentsString()
        );

        return $hash;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param Item $item
     *
     * @return $this
     */
    public function setItem($item)
    {
        $this->item = $item;

        return $this;
    }

    /**
     * @return int
     */
    public function getPrestige()
    {
        return $this->prestige;
    }

    /**
     * @param int $prestige
     *
     * @return $this
     */
    public function setPrestige($prestige)
    {
        $this->prestige = $prestige;

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
     * @return string
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * @param string $resourceId
     *
     * @return $this
     */
    public function setResourceId($resourceId)
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTalents()
    {
        return unserialize($this->getTalentsString());
    }

    /**
     * @return string
     */
    public function getTalentsString()
    {
        return $this->talentsString;
    }

    /**
     * @param string $talentsString
     *
     * @return $this
     */
    public function setTalentsString($talentsString)
    {
        $this->talentsString = $talentsString;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProperties()
    {
        return unserialize($this->getPropertiesString());
    }

    /**
     * @return mixed
     */
    public function getPropertiesString()
    {
        return $this->propertiesString;
    }

    /**
     * @param mixed $propertiesString
     *
     * @return $this
     */
    public function setPropertiesString($propertiesString)
    {
        $this->propertiesString = $propertiesString;

        return $this;
    }

    /**
     * @return string
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @param string $player
     *
     * @return $this
     */
    public function setPlayer($player)
    {
        $this->player = $player;

        return $this;
    }
}
