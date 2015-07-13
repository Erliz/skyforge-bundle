<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   31.03.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Setting
 *
 * @ORM\Table(name="pantheon")
 * @ORM\Entity
 */
class Pantheon implements CommunityInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="img", type="string", length=255, nullable=true)
     */
    private $img;

    /**
     * @var PlayerCollection
     *
     * @ORM\OneToMany(targetEntity="Player", mappedBy="pantheon")
     */
    private $members;

    /**
     * @var int
     *
     * @ORM\Column(name="is_active", type="integer")
     */
    private $isActive;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private $updatedAt;

    public function __construct()
    {
        $this->members = new PlayerCollection();
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getImg()
    {
        return $this->img;
    }

    /**
     * @param string $img
     *
     * @return $this
     */
    public function setImg($img)
    {
        $this->img = $img;

        return $this;
    }

    public function jsonSerialize()
    {
        $this->toArray();
    }

    public function toArray()
    {
        $ids = array();
        foreach ($this->getMembers() as $member) {
            $ids[] = $member->getId();
        }

        return array(
            'id'   => $this->getId(),
            'name' => $this->getName(),
            'img'  => $this->getImg(),
            'members' => $ids,
            'updated_at' => $this->getUpdatedAt()->format('Y-m-d H:i:s')
        );
    }

    /**
     * @return PlayerCollection
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @param int $isActive
     *
     * @return $this
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @param Player $player
     *
     * @return $this
     */
    public function addMember(Player $player)
    {
        $members = $this->getMembers();
        if(!$members->contains($player)) {
            $members->add($player);
        }

        return $this;
    }
}
