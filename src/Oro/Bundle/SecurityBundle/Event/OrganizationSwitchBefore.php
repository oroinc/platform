<?php

namespace Oro\Bundle\SecurityBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationSwitchBefore extends Event
{
    const NAME = 'oro_security.event.organization_switch.before';

    /** @var User */
    protected $user;

    /** @var Organization */
    protected $organization;

    /** @var Organization */
    protected $organizationToSwitch;

    /**
     * @param User         $user
     * @param Organization $organization
     * @param Organization $organizationToSwitch
     */
    public function __construct(User $user, Organization $organization, Organization $organizationToSwitch)
    {
        $this->user                 = $user;
        $this->organization         = $organization;
        $this->organizationToSwitch = $organizationToSwitch;
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

    /**
     * Organization that user request to switch into
     *
     * @return Organization
     */
    public function getOrganizationToSwitch()
    {
        return $this->organizationToSwitch;
    }

    /**
     * Possibility to change organization that will be active afterwards
     *
     * @param Organization $organizationToSwitch
     */
    public function setOrganizationToSwitch(Organization $organizationToSwitch)
    {
        $this->organizationToSwitch = $organizationToSwitch;
    }
}
