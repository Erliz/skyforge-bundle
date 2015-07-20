<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   20.07.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * PantheonDateStat
 *
 * @ORM\Table(name="pantheon_date_stat", uniqueConstraints={@ORM\UniqueConstraint(columns={"pantheon", "date"})})
 * @ORM\Entity
 */
class PantheonDateStat
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
     * @ORM\ManyToOne(targetEntity="Pantheon", inversedBy="dateStat")
     * @ORM\JoinColumn(name="pantheon", referencedColumnName="id", nullable=false)
     */
    private $pantheon;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var int
     *
     * @ORM\Column(name="members_count", type="integer", nullable=true)
     */
    private $membersCount;

    /**
     * @var int
     *
     * @ORM\Column(name="max_prestige", type="integer", nullable=true)
     */
    private $maxPrestige;

    /**
     * @var int
     *
     * @ORM\Column(name="sum_prestige", type="integer", nullable=true)
     */
    private $sumPrestige;

    /**
     * @var int
     *
     * @ORM\Column(name="avg_prestige", type="integer", nullable=true)
     */
    private $avgPrestige;

    /**
     * @var int
     *
     * @ORM\Column(name="pve_sum_time", type="integer", nullable=true)
     */
    private $pveSumTime;

    /**
     * @var int
     *
     * @ORM\Column(name="pve_sum_mob_kills", type="integer", nullable=true)
     */
    private $pveSumMobKills;

    /**
     * @var int
     *
     * @ORM\Column(name="pve_sum_boss_kills", type="integer", nullable=true)
     */
    private $pveSumBossKills;

    /**
     * @var int
     *
     * @ORM\Column(name="pve_sum_deaths", type="integer", nullable=true)
     */
    private $pveSumDeaths;

    /**
     * @var int
     *
     * @ORM\Column(name="pvp_sum_time", type="integer", nullable=true)
     */
    private $pvpSumTime;

    /**
     * @var int
     *
     * @ORM\Column(name="pvp_sum_kills", type="integer", nullable=true)
     */
    private $pvpSumKills;

    /**
     * @var int
     *
     * @ORM\Column(name="pvp_sum_deaths", type="integer", nullable=true)
     */
    private $pvpSumDeaths;

    /**
     * @var int
     *
     * @ORM\Column(name="pvp_sum_assists", type="integer", nullable=true)
     */
    private $pvpSumAssists;

    public function getPveKdr()
    {
        if (!$this->pveSumDeaths) {
            return 0;
        }

        return round($this->pveSumBossKills / $this->pveSumDeaths, 2);
    }

    public function getPvpKdr()
    {
        if (!$this->pvpSumDeaths) {
            return 0;
        }

        return round(($this->pvpSumKills + $this->pvpSumAssists * 0.25) / $this->pvpSumDeaths, 2);
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
     * @return int
     */
    public function getPveSumMobKills()
    {
        return $this->pveSumMobKills;
    }

    /**
     * @param int $pveSumMobKills
     *
     * @return $this
     */
    public function setPveSumMobKills($pveSumMobKills)
    {
        $this->pveSumMobKills = $pveSumMobKills;

        return $this;
    }

    /**
     * @return int
     */
    public function getPveSumBossKills()
    {
        return $this->pveSumBossKills;
    }

    /**
     * @param int $pveSumBossKills
     *
     * @return $this
     */
    public function setPveSumBossKills($pveSumBossKills)
    {
        $this->pveSumBossKills = $pveSumBossKills;

        return $this;
    }

    /**
     * @return int
     */
    public function getPveSumDeaths()
    {
        return $this->pveSumDeaths;
    }

    /**
     * @param int $pveSumDeaths
     *
     * @return $this
     */
    public function setPveSumDeaths($pveSumDeaths)
    {
        $this->pveSumDeaths = $pveSumDeaths;

        return $this;
    }

    /**
     * @return int
     */
    public function getPvpSumKills()
    {
        return $this->pvpSumKills;
    }

    /**
     * @param int $pvpSumKills
     *
     * @return $this
     */
    public function setPvpSumKills($pvpSumKills)
    {
        $this->pvpSumKills = $pvpSumKills;

        return $this;
    }

    /**
     * @return int
     */
    public function getPvpSumDeaths()
    {
        return $this->pvpSumDeaths;
    }

    /**
     * @param int $pvpSumDeaths
     *
     * @return $this
     */
    public function setPvpSumDeaths($pvpSumDeaths)
    {
        $this->pvpSumDeaths = $pvpSumDeaths;

        return $this;
    }

    /**
     * @return int
     */
    public function getPvpSumAssists()
    {
        return $this->pvpSumAssists;
    }

    /**
     * @param int $pvpSumAssists
     *
     * @return $this
     */
    public function setPvpSumAssists($pvpSumAssists)
    {
        $this->pvpSumAssists = $pvpSumAssists;

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
            'date' => $this->getDate()->format('Y-m-d'),
            'pve_boss_kills' => $this->getPveSumBossKills(),
            'pve_mob_kills' => $this->getPveSumMobKills(),
            'pve_deaths' => $this->getPveSumDeaths(),
            'pvp_kills' => $this->getPvpSumKills(),
            'pvp_deaths' => $this->getPvpSumDeaths(),
            'pvp_assists' => $this->getPvpSumAssists()
        );

        if ($this->getPantheon()) {
            $result['pantheon'] = $this->getPantheon()->getId();
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getPvpSumTime()
    {
        return $this->pvpSumTime;
    }

    /**
     * @param int $pvpSumTime
     *
     * @return $this
     */
    public function setPvpSumTime($pvpSumTime)
    {
        $this->pvpSumTime = $pvpSumTime;

        return $this;
    }

    /**
     * @return int
     */
    public function getMembersCount()
    {
        return $this->membersCount;
    }

    /**
     * @param int $membersCount
     *
     * @return $this
     */
    public function setMembersCount($membersCount)
    {
        $this->membersCount = $membersCount;

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
    public function getSumPrestige()
    {
        return $this->sumPrestige;
    }

    /**
     * @param int $sumPrestige
     *
     * @return $this
     */
    public function setSumPrestige($sumPrestige)
    {
        $this->sumPrestige = $sumPrestige;

        return $this;
    }

    /**
     * @return int
     */
    public function getAvgPrestige()
    {
        return $this->avgPrestige;
    }

    /**
     * @param int $avgPrestige
     *
     * @return $this
     */
    public function setAvgPrestige($avgPrestige)
    {
        $this->avgPrestige = $avgPrestige;

        return $this;
    }

    /**
     * @return int
     */
    public function getPveSumTime()
    {
        return $this->pveSumTime;
    }

    /**
     * @param int $pveSumTime
     *
     * @return $this
     */
    public function setPveSumTime($pveSumTime)
    {
        $this->pveSumTime = $pveSumTime;

        return $this;
    }
}
