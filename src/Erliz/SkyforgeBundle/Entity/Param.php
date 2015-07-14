<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   15.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use Doctrine\ORM\Mapping as ORM;

/**
 * Item
 *
 * @ORM\Table(name="param")
 * @ORM\Entity
 */
class Param
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
    private $description;
}