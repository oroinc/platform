<?php

namespace Oro\Bundle\SecurityBundle\Authentication;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Provides a set of methods to manage the current security token or get its parts.
 * The "hasUser", "getUser", "getUserId", "getOrganization" and "getOrganizationId" methods
 * are intended to work with "AbstractUser" and "Organization" ORM entities.
 * If you expects that a security token contains not ORM entities for an user or an organization
 * use "getToken" method and extracts the token parts manually. Also in this case consider
 * injecting TokenStorageInterface into your service instead of TokenAccessorInterface.
 * @see \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface
 */
interface TokenAccessorInterface extends TokenStorageInterface
{
    /**
     * Checks whether an user entity exists in the current security token.
     *
     * @return bool
     */
    public function hasUser();

    /**
     * Gets an user entity from the current security token.
     *
     * @return AbstractUser|null
     */
    public function getUser();

    /**
     * Gets identifier of an user entity from the current security token.
     *
     * @return int|null
     */
    public function getUserId();

    /**
     * Gets an organization entity from the current security token.
     *
     * @return Organization|null
     */
    public function getOrganization();

    /**
     * Gets identifier of an organization entity from the current security token.
     *
     * @return int|null
     */
    public function getOrganizationId();
}
