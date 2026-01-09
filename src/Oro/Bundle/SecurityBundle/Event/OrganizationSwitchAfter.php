<?php

namespace Oro\Bundle\SecurityBundle\Event;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a user successfully switches to a different organization.
 *
 * This event is triggered after the organization switch operation has been completed,
 * allowing listeners to perform post-switch actions such as updating user preferences,
 * clearing caches, or notifying other systems of the organization change.
 */
class OrganizationSwitchAfter extends Event
{
    public const NAME = 'oro_security.event.organization_switch.after';

    /** @var User */
    protected $user;

    /** @var Organization */
    protected $organization;

    public function __construct(User $user, Organization $organization)
    {
        $this->user         = $user;
        $this->organization = $organization;
    }

    /**
     * Current active organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Current logged in user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }
}
