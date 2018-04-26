<?php

namespace Oro\Bundle\UserBundle\Security;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Provides an interface for entities that are used to store API keys for users.
 */
interface UserApiKeyInterface
{
    /**
     * Indicates whether this API key is enabled.
     *
     * @return bool
     */
    public function isEnabled();

    /**
     * Gets API key.
     *
     * @return string
     */
    public function getApiKey();

    /**
     * Gets a user this API key belongs to.
     *
     * @return UserInterface
     */
    public function getUser();

    /**
     * Gets an organization this API key belongs to.
     *
     * @return Organization
     */
    public function getOrganization();
}
