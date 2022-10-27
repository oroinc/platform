<?php

namespace Oro\Bundle\SecurityBundle\ORM\Walker;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\SecurityBundle\AccessRule\AccessRuleExecutor;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationAwareTokenInterface;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The default implementation of a factory to create AccessRuleWalkerContext object.
 */
class AccessRuleWalkerContextFactory implements AccessRuleWalkerContextFactoryInterface
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var AccessRuleExecutor */
    private $accessRuleExecutor;

    public function __construct(TokenStorageInterface $tokenStorage, AccessRuleExecutor $accessRuleExecutor)
    {
        $this->tokenStorage = $tokenStorage;
        $this->accessRuleExecutor = $accessRuleExecutor;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(string $permission): AccessRuleWalkerContext
    {
        $token = $this->tokenStorage->getToken();
        $userId = null;
        $userClass = null;
        $organizationId = null;
        if ($token) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                $userId = $user->getId();
                $userClass = ClassUtils::getClass($user);
            }
            if ($token instanceof OrganizationAwareTokenInterface) {
                $organizationId = $token->getOrganization()->getId();
            }
        }

        return new AccessRuleWalkerContext(
            $this->accessRuleExecutor,
            $permission,
            $userClass,
            $userId,
            $organizationId
        );
    }
}
