<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   01.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * PlayerDateStat
 *
 * @ORM\Table(name="player_date_stat", uniqueConstraints={@ORM\UniqueConstraint(columns={"player", "date"})})
 * @ORM\Entity(repositoryClass="Erliz\SkyforgeBundle\Repository\PlayerDateStatRepository")
 */
class PlayerDateStat
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
     * @ORM\ManyToOne(targetEntity="Player", inversedBy="dateStat")
     * @ORM\JoinColumn(name="player", referencedColumnName="id", nullable=false)
     */
    private $player;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var Role
     *
     * @ORM\ManyToOne(targetEntity="Role")
     * @ORM\JoinColumn(name="role", referencedColumnName="id", nullable=false)
     */
    private $role;

    /**
     * @var Pantheon
     *
     * @ORM\ManyToOne(targetEntity="Pantheon")
     * @ORM\JoinColumn(name="pantheon", referencedColumnName="id", nullable=true)
     */
    private $pantheon;

    /**
     * @var int
     *
     * @ORM\Column(name="max_prestige", type="integer", nullable=true)
     */
    private $maxPrestige;

    /**
     * @var int
     *
     * @ORM\Column(name="current_prestige", type="integer", nullable=true)
     */
    private $currentPrestige;

    /**
     * @var int
     *
     * @ORM\Column(name="total_time", type="integer", nullable=true)
     */
    private $totalTime;

    /**
     * @var int
     *
     * @ORM\Column(name="pvp_time", type="integer", nullable=true)
     */
    private $pvpTime;

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
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     *
     * @return $this
     */
    public function setDate(DateTime $date)
    {
        $this->date = $date;

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
    public function getMaxPrestige()
    {
        return $this->maxPrestige;
    }

    /**
     * @param int $maxPrestige
     *
     * @return $this
     */
    public function setMaxPrestige($maxPrestige)
    {
        $this->maxPrestige = $maxPrestige;

        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPrestige()
    {
        return $this->currentPrestige;
    }

    /**
     * @param int $currentPrestige
     *
     * @return $this
     */
    public function setCurrentPrestige($currentPrestige)
    {
        $this->currentPrestige = $currentPrestige;

        return $this;
    }

    /**
     * @return int
     */
    public function getTotalTime()
    {
        return $this->totalTime;
    }

    /**
     * @param int $totalTime
     *
     * @return $this
     */
    public function setTotalTime($totalTime)
    {
        $this->totalTime = $totalTime;

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
     * @return Pantheon
     */
    public function getPantheon()
    {
        return $this->pantheon;
    }

    /**
     * @param Pantheon $pantheon
     *
     * @return $this
     */
    public function setPantheon(Pantheon $pantheon = null)
    {
        $this->pantheon = $pantheon;

        return $this;
    }

    public function JsonSerialize() {
        return $this->toArray();
    }

    public function toArray() {

        $result = array(
            'id' => $this->getId(),
            'player' => $this->getPlayer()->getId(),
            'date' => $this->getDate()->format('Y-m-d'),
            'role' => $this->getRole()->getName(),
            'max_prestige' => $this->getMaxPrestige(),
            'current_prestige' => $this->getCurrentPrestige(),
            'total_time' => $this->getTotalTime(),
            'pve_boss_kills' => $this->getPveBossKills(),
            'pve_mob_kills' => $this->getPveMobKills(),
            'pve_deaths' => $this->getPveDeaths(),
            'pvp_kills' => $this->getPvpKills(),
            'pvp_deaths' => $this->getPvpDeaths(),
            'pvp_assists' => $this->getPvpAssists()
        );

        if ($this->getPantheon()) {
            $result['pantheon'] = $this->getPantheon()->getId();
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getPvpTime()
    {
        return $this->pvpTime;
    }

    /**
     * @param int $pvpTime
     *
     * @return $this
     */
    public function setPvpTime($pvpTime)
    {
        $this->pvpTime = $pvpTime;

        return $this;
    }
}
