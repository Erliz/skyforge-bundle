<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   08.06.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use DateTime;
use JsonSerializable;

interface CommunityInterface extends JsonSerializable
{
    /**
     * @return string
     */
    public function getId();

    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getImg();

    /**
     * @return PlayerCollection
     */
    public function getMembers();

    /**
     * @param Player $player
     *
     * @return $this
     */
    public function addMember(Player $player);

    /**
     * @return DateTime
     */
    public function getUpdatedAt();

    /**
     * @return array
     */
    public function toArray();

    /**
     * @param DateTime $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(DateTime $updatedAt);
}
