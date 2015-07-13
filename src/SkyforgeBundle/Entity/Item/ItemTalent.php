<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   15.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity\Item;


use Doctrine\ORM\Mapping as ORM;

/**
 * ItemTalent
 *
 * @ORM\Table(name="item_talent")
 * @ORM\Entity
 */
class ItemTalent
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=false)
     */
    private $description;

    /**
     * @var int
     *
     * @ORM\Column(name="common_value", type="string", nullable=true)
     */
    private $commonValue;

    /**
     * @var int
     *
     * @ORM\Column(name="uncommon_value", type="string", nullable=true)
     */
    private $uncommonValue;

    /**
     * @var int
     *
     * @ORM\Column(name="rare_value", type="string", nullable=true)
     */
    private $rareValue;

    /**
     * @var int
     *
     * @ORM\Column(name="epic_value", type="string", nullable=true)
     */
    private $epicValue;

    /**
     * @var int
     *
     * @ORM\Column(name="legendary_value", type="string", nullable=true)
     */
    private $legendaryValue;

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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return int
     */
    public function getCommonValue()
    {
        return $this->commonValue;
    }

    /**
     * @param int $commonValue
     *
     * @return $this
     */
    public function setCommonValue($commonValue)
    {
        $this->commonValue = $commonValue;

        return $this;
    }

    /**
     * @return int
     */
    public function getUncommonValue()
    {
        return $this->uncommonValue;
    }

    /**
     * @param int $uncommonValue
     *
     * @return $this
     */
    public function setUncommonValue($uncommonValue)
    {
        $this->uncommonValue = $uncommonValue;

        return $this;
    }

    /**
     * @return int
     */
    public function getRareValue()
    {
        return $this->rareValue;
    }

    /**
     * @param int $rareValue
     *
     * @return $this
     */
    public function setRareValue($rareValue)
    {
        $this->rareValue = $rareValue;

        return $this;
    }

    /**
     * @return int
     */
    public function getEpicValue()
    {
        return $this->epicValue;
    }

    /**
     * @param int $epicValue
     *
     * @return $this
     */
    public function setEpicValue($epicValue)
    {
        $this->epicValue = $epicValue;

        return $this;
    }

    /**
     * @return int
     */
    public function getLegendaryValue()
    {
        return $this->legendaryValue;
    }

    /**
     * @param int $legendaryValue
     *
     * @return $this
     */
    public function setLegendaryValue($legendaryValue)
    {
        $this->legendaryValue = $legendaryValue;

        return $this;
    }
}
