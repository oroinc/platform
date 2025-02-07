<?php

namespace Oro\Bundle\TestFrameworkBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\SecurityBundle\Authentication\Token\RolesAndOrganizationAwareTokenTrait;
use Oro\Bundle\SecurityBundle\Authentication\Token\RolesAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * The API test token used in functional tests.
 */
class TestApiToken extends UsernamePasswordToken implements OrganizationAwareTokenInterface, RolesAwareTokenInterface
{
    use RolesAndOrganizationAwareTokenTrait;

    public function __construct(AbstractUser $user, string $firewallName, array $roles = [])
    {
        parent::__construct($user, $firewallName, $this->initRoles($roles));
    }
}
