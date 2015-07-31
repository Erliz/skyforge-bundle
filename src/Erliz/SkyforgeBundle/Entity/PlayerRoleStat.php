<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   01.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PlayerRoleStat
 *
 * @ORM\Table(name="player_role_stat", uniqueConstraints={@ORM\UniqueConstraint(columns={"player", "role"})})
 * @ORM\Entity
 */
class PlayerRoleStat
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Player
     *
     * @ORM\ManyToOne(targetEntity="Player", inversedBy="roleStat")
     * @ORM\JoinColumn(name="player", referencedColumnName="id", nullable=false)
     */
    private $player;

    /**
     * @var Role
     *
     * @ORM\ManyToOne(targetEntity="Role")
     * @ORM\JoinColumn(name="role", referencedColumnName="id", nullable=false)
     */
    private $role;

    /**
     * @var int
     *
     * @ORM\Column(name="seconds_played", type="integer", nullable=true)
     */
    private $secondsPlayed;

    /**
     * @var int
     *
     * @ORM\Column(name="seconds_active_played", type="integer", nullable=true)
     */
    private $secondsActivePlayed;

    /**
     * @var int
     *
     * @ORM\Column(name="pve_mob_kills", type="integer", nullable=true)
     */
    private $pveMobKills;

    /**
     * @var int
     *
     * @ORM\Column(name="pve_boss_kills", type="integer", nullable=true)
     */
    private $pveBossKills;

    /**
     * @var int
     *
     * @ORM\Column(name="pve_deaths", type="integer", nullable=true)
     */
    private $pveDeaths;

    /**
     * @var int
     *
     * @ORM\Column(name="pvp_kills", type="integer", nullable=true)
     */
    private $pvpKills;

    /**
     * @var int
     *
     * @ORM\Column(name="pvp_deaths", type="integer", nullable=true)
     */
    private $pvpDeaths;

    /**
     * @var int
     *
     * @ORM\Column(name="pvp_assists", type="integer", nullable=true)
     */
    private $pvpAssists;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="modified_at", type="datetime")
     */
    private $modifiedAt;

    /**
     * @return Player
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @param Player $player
     *
     * @return $this
     */
    public function setPlayer(Player $player)
    {
        $this->player = $player;

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
     * @return int
     */
    public function getPveMobKills()
    {
        return $this->pveMobKills;
    }

    /**
     * @param int $pveMobKills
     *
     * @return $this
     */
    public function setPveMobKills($pveMobKills)
    {
        $this->pveMobKills = $pveMobKills;

        return $this;
    }

    /**
     * @return int
     */
    public function getPveBossKills()
    {
        return $this->pveBossKills;
    }

    /**
     * @param int $pveBossKills
     *
     * @return $this
     */
    public function setPveBossKills($pveBossKills)
    {
        $this->pveBossKills = $pveBossKills;

        return $this;
    }

    /**
     * @return int
     */
    public function getPveDeaths()
    {
        return $this->pveDeaths;
    }

    /**
     * @param int $pveDeaths
     *
     * @return $this
     */
    public function setPveDeaths($pveDeaths)
    {
        $this->pveDeaths = $pveDeaths;

        return $this;
    }

    /**
     * @return int
     */
    public function getPvpKills()
    {
        return $this->pvpKills;
    }

    /**
     * @param int $pvpKills
     *
     * @return $this
     */
    public function setPvpKills($pvpKills)
    {
        $this->pvpKills = $pvpKills;

        return $this;
    }

    /**
     * @return int
     */
    public function getPvpDeaths()
    {
        return $this->pvpDeaths;
    }

    /**
     * @param int $pvpDeaths
     *
     * @return $this
     */
    public function setPvpDeaths($pvpDeaths)
    {
        $this->pvpDeaths = $pvpDeaths;

        return $this;
    }

    /**
     * @return int
     */
    public function getPvpAssists()
    {
        return $this->pvpAssists;
    }

    /**
     * @param int $pvpAssists
     *
     * @return $this
     */
    public function setPvpAssists($pvpAssists)
    {
        $this->pvpAssists = $pvpAssists;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getSecondsPlayed()
    {
        return $this->secondsPlayed;
    }

    /**
     * @param int $secondsPlayed
     *
     * @return $this
     */
    public function setSecondsPlayed($secondsPlayed)
    {
        $this->secondsPlayed = $secondsPlayed;

        return $this;
    }

    /**
     * @return int
     */
    public function getSecondsActivePlayed()
    {
        return $this->secondsActivePlayed;
    }

    /**
     * @param int $secondsActivePlayed
     *
     * @return $this
     */
    public function setSecondsActivePlayed($secondsActivePlayed)
    {
        $this->secondsActivePlayed = $secondsActivePlayed;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    public function JsonSerialize() {
        return $this->toArray();
    }

    public function toArray() {
        return array(
            'id'                    => $this->getId(),
            'player'                => $this->getPlayer()->getId(),
            'role'                  => $this->getRole()->getName(),
            'seconds_played'        => $this->getSecondsPlayed(),
            'seconds_active_played' => $this->getSecondsActivePlayed(),
            'pve_boss_kills'        => $this->getPveBossKills(),
            'pve_mob_kills'         => $this->getPveMobKills(),
            'pve_deaths'            => $this->getPveDeaths(),
            'pvp_kills'             => $this->getPvpKills(),
            'pvp_deaths'            => $this->getPvpDeaths(),
            'pvp_assists'           => $this->getPvpAssists(),
            'modified_at'           => $this->getModifiedAt()->format('Y-m-d')
        );
    }

    /**
     * @param DateTime $modifiedAt
     *
     * @return $this
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }
}
