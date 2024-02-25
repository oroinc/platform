<?php

namespace Oro\Bundle\EntityConfigBundle\Audit\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Holds links to the information about changes in the tracked entities.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_entity_config_log')]
#[ORM\HasLifecycleCallbacks]
class ConfigLog
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?UserInterface $user = null;

    /**
     * @var Collection<int, ConfigLogDiff>
     */
    #[ORM\OneToMany(mappedBy: 'log', targetEntity: ConfigLogDiff::class, cascade: ['all'])]
    protected ?Collection $diffs = null;

    #[ORM\Column(name: 'logged_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $loggedAt = null;

    public function __construct()
    {
        $this->diffs = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param \DateTime $loggedAt
     * @return $this
     */
    public function setLoggedAt($loggedAt)
    {
        $this->loggedAt = $loggedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLoggedAt()
    {
        return $this->loggedAt;
    }

    /**
     * @param UserInterface $user
     * @return $this
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param ConfigLogDiff[] $diffs
     * @return $this
     */
    public function setDiffs($diffs)
    {
        $this->diffs = $diffs;

        return $this;
    }

    /**
     * @param ConfigLogDiff $diff
     * @return $this
     */
    public function addDiff(ConfigLogDiff $diff)
    {
        if (!$this->diffs->contains($diff)) {
            $diff->setLog($this);
            $this->diffs->add($diff);
        }

        return $this;
    }

    /**
     * @return ConfigLogDiff[]|ArrayCollection
     */
    public function getDiffs()
    {
        return $this->diffs;
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        $this->loggedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
