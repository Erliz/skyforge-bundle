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
 * @ORM\Table(name="item")
 * @ORM\Entity
 */
class Item
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, unique=true, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="img", type="string", length=255, nullable=true)
     */
    private $img;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="is_wearable", type="smallint", length=1, nullable=false)
     */
    private $isWearable;

    /**
     * @var Role
     *
     * @ORM\ManyToOne(targetEntity="Erliz\SkyforgeBundle\Entity\Role")
     * @ORM\JoinColumn(name="role", referencedColumnName="id", nullable=true)
     */
    private $role;

    /**
     * @var ParamCollection
     *
     * @ORM\OneToMany(targetEntity="Erliz\SkyforgeBundle\Entity\Param", mappedBy="id")
     */
    private $params;

    /**
     * @var ItemTalentCollection
     *
     * @ORM\OneToMany(targetEntity="ItemTalent", mappedBy="id")
     */
    private $talents;

    public function __construct()
    {
        $this->talents = new ItemTalentCollection();
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
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param Role $role
     *
     * @return $this
     */
    public function setRole(Role $role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return ItemTalentCollection
     */
    public function getTalents()
    {
        return $this->talents;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIsWearable()
    {
        return $this->isWearable;
    }

    /**
     * @param string $isWearable
     *
     * @return $this
     */
    public function setIsWearable($isWearable)
    {
        $this->isWearable = $isWearable;

        return $this;
    }
}
