<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   01.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Role.
 *
 * @ORM\Table(name="role")
 * @ORM\Entity
 */
class Role  // implements JsonSerializable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="smallint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @ORM\Column(name="img", type="string", length=255, unique=true, nullable=false)
     */
    private $img;

    /**
     * @var int resource id from portal.sf.mail.ru
     *
     * @ORM\Column(name="resource_id", type="integer", unique=true, nullable=true)
     */
    private $resourceId;

    function __construct()
    {
        $this->players = new PlayerCollection();
    }

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
        return array(
            'id'          => $this->getId(),
            'name'        => $this->getName(),
            'img'         => $this->getImg(),
            'resource_id' => $this->getResourceId(),
        );
    }

    /**
     * @return PlayerCollection
     */
    public function getPlayers()
    {
        return $this->players;
    }

    /**
     * @return int
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * @param int $resourceId
     *
     * @return $this
     */
    public function setResourceId($resourceId)
    {
        $this->resourceId = $resourceId;

        return $this;
    }
}
