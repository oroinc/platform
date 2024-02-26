<?php

namespace Oro\Bundle\ActivityListBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\UserBundle\Entity\User;

/**
* Entity that represents Activity Owner
*
*/
#[ORM\Entity]
#[ORM\Table(name: 'oro_activity_owner')]
#[ORM\UniqueConstraint(name: 'UNQ_activity_owner', columns: ['activity_id', 'user_id'])]
class ActivityOwner
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ActivityList::class, inversedBy: 'activityOwners')]
    #[ORM\JoinColumn(name: 'activity_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?ActivityList $activity = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id')]
    protected ?OrganizationInterface $organization = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    protected ?User $user = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Set organization
     *
     * @param Organization|null $organization
     *
     * @return self
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get activity
     *
     * @return ActivityList
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set activity
     *
     * @param ActivityList|null $activity
     *
     * @return self
     */
    public function setActivity(ActivityList $activity = null)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * @param User|null $user
     *
     * @return self
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Check existing owner in given array stack
     * @param array $stack
     * @return bool
     */
    public function isOwnerInCollection($stack)
    {
        $criteria = new Criteria();
        $criteria
            ->andWhere($criteria->expr()->eq('organization', $this->getOrganization()))
            ->andWhere($criteria->expr()->eq('user', $this->getUser()));

        return (bool) count($stack->matching($criteria));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
