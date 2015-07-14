<?php
/**
 * @author Stanislav Vetlovskiy
 * @date   01.04.2015
 */

namespace Erliz\SkyforgeBundle\Entity;


use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JsonSerializable;

/**
 * Player.
 *
 * @ORM\Table(name="player")
 * @ORM\Entity(repositoryClass="Erliz\SkyforgeBundle\Repository\PlayerRepository")
 */
class Player implements JsonSerializable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="nick", type="string", length=255, nullable=false)
     */
    private $nick;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="img", type="string", length=255, nullable=true)
     */
    private $img;

    /**
     * @var Role
     *
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="roles")
     * @ORM\JoinColumn(name="current_role", referencedColumnName="id", nullable=true)
     */
    private $currentRole;

    /**
     * @var Pantheon
     *
     * @ORM\ManyToOne(targetEntity="Pantheon", inversedBy="members")
     * @ORM\JoinColumn(name="pantheon", referencedColumnName="id", nullable=true)
     */
    private $pantheon;

    /**
     * @var Community[]
     *
     * @ORM\ManyToMany(targetEntity="Community", inversedBy="members")
     */
    private $communities;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="modified_at", type="datetime")
     */
    private $modifiedAt;

    /**
     * @var PlayerDateStat[]
     *
     * @ORM\OneToMany(targetEntity="PlayerDateStat", mappedBy="player")
     * @ORM\OrderBy({"date" = "DESC"})
     */
    private $dateStat;

    /**
     * @var PlayerRoleStat[]
     *
     * @ORM\OneToMany(targetEntity="PlayerRoleStat", mappedBy="player")
     */
    private $roleStat;

    /**
     * @var BillingTransaction[]
     * 
     * @ORM\OneToMany(targetEntity="BillingTransaction", mappedBy="player")
     */
    private $billingTransactions;

    public function __construct()
    {
        $this->communities = new ArrayCollection();
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
        $ids = array();
        foreach ($this->getCommunities() as $community) {
            $ids[] = $community->getId();
        }
        $result = array(
            'id'           => $this->getId(),
            'nick'         => $this->getNick(),
            'name'         => $this->getName(),
            'img'          => $this->getImg(),
            'communities'  => $ids,
            'created_at'   => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'modified_at'  => $this->getModifiedAt()->format('Y-m-d H:i:s')
        );

        if($this->getCurrentRole()) {
            $result['current_role'] = $this->getCurrentRole()->getName();
        }
        if($this->getPantheon()) {
            $result['pantheon'] = $this->getPantheon()->getName();
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getNick()
    {
        return $this->nick;
    }

    /**
     * @param string $nick
     *
     * @return $this
     */
    public function setNick($nick)
    {
        $this->nick = $nick;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return Role
     */
    public function getCurrentRole()
    {
        return $this->currentRole;
    }

    /**
     * @param Role $currentRole
     *
     * @return $this
     */
    public function setCurrentRole(Role $currentRole)
    {
        $this->currentRole = $currentRole;

        return $this;
    }

    /**
     * @return $this
     */
    public function removeRole()
    {
        $this->currentRole = null;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * @return Pantheon|null
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
    public function setPantheon(Pantheon $pantheon)
    {
        $this->pantheon = $pantheon;

        return $this;
    }

    /**
     * @return $this
     */
    public function removePantheon()
    {
        $this->pantheon->getMembers()->removeElement($this);
        $this->pantheon = null;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getDateStat()
    {
        return $this->dateStat;
    }

    /**
     * @return ArrayCollection
     */
    public function getCommunities()
    {
        return $this->communities;
    }

    /**
     * @return PlayerRoleStat
     */
    public function getLongestActiveRoleStat()
    {
        $roleStats = $this->getRoleStat();

        /** @var PlayerRoleStat $roleStat */
        $roleStat = null;
        foreach ($roleStats as $stat) {
            if (is_null($roleStat) || $roleStat->getSecondsActivePlayed() < $stat->getSecondsActivePlayed()) {
                $roleStat = $stat;
            }
        }

        return $roleStat;
    }

    /**
     * @return PlayerRoleStat[]
     */
    public function getRoleStat()
    {
        return $this->roleStat;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
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

    /**
     * @return BillingTransaction[]
     */
    public function getBillingTransactions()
    {
        return $this->billingTransactions;
    }

    /**
     * @param BillingTransaction[] $billingTransactions
     *
     * @return $this
     */
    public function setBillingTransactions($billingTransactions)
    {
        $this->billingTransactions = $billingTransactions;

        return $this;
    }

    public function getPvpKills()
    {
        $killsAggregate = 0;
        foreach ($this->getRoleStat() as $role) {
            $killsAggregate += $role->getPvpKills();
        }

        return $killsAggregate;
    }

    public function getPvpAssists()
    {
        $assistsAggregate = 0;
        foreach ($this->getRoleStat() as $role) {
            $assistsAggregate += $role->getPvpAssists();
        }

        return $assistsAggregate;
    }

    /**
     * @return int
     */
    public function getBalance()
    {
        $balance = 0;

        foreach ($this->getBillingTransactions() as $transaction)
        {
            switch ($transaction->getAction()){
                case BillingTransaction::DEPOSIT_ACTION:
                case BillingTransaction::TRANSFER_TO_ACTION:
                    $balance += $transaction->getAmount();
                    break;
//                case BillingTransaction::WITHDRAW_ACTION:
                case BillingTransaction::TRANSFER_FROM_ACTION:
                case BillingTransaction::TAX_ACTION:
                    $balance -= $transaction->getAmount();
            }
        }

        return $balance;
    }
}
