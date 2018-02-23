<?php

namespace Oro\Bundle\UserBundle\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Authentication\Token\ImpersonationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class UserPermissionApiEntityManager extends ApiEntityManager
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var AclExtensionSelector */
    protected $aclSelector;

    /**
     * @param string                        $class
     * @param ObjectManager                 $om
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     * @param AclExtensionSelector          $aclSelector
     */
    public function __construct(
        $class,
        ObjectManager $om,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        AclExtensionSelector $aclSelector
    ) {
        parent::__construct($class, $om);
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->aclSelector = $aclSelector;
    }

    /**
     * Gets permissions of the given user
     *
     * @param User          $user
     * @param Criteria|null $filters
     *
     * @return array
     */
    public function getUserPermissions(User $user, Criteria $filters = null)
    {
        $entityAclExtension = $this->aclSelector->select($user);

        $resources = array_map(
            function (AclClassInfo $class) use ($entityAclExtension) {
                return [
                    'type'     => $entityAclExtension->getExtensionKey(),
                    'resource' => $class->getClassName()
                ];
            },
            $entityAclExtension->getClasses()
        );
        if ($filters) {
            $collection = new ArrayCollection($resources);
            $resources = $collection->matching($filters)->toArray();
        }

        $result = [];
        $originalToken = $this->impersonateUser($user);
        try {
            foreach ($resources as $resource) {
                $oid = new ObjectIdentity($resource['type'], $resource['resource']);

                $permissions = [];
                foreach ($entityAclExtension->getAllowedPermissions($oid) as $permission) {
                    if ($this->authorizationChecker->isGranted($permission, $oid)) {
                        $permissions[] = $permission;
                    }
                }

                $result[] = array_merge($resource, ['permissions' => $permissions]);
            }

            $this->undoImpersonation($originalToken);
        } catch (\Exception $e) {
            $this->undoImpersonation($originalToken);
            throw $e;
        }

        return $result;
    }

    /**
     * Switches the security context to the given user
     *
     * @param User $user
     *
     * @return TokenInterface|null The previous security token
     *
     * @throws \UnexpectedValueException
     * @throws AccessDeniedException
     */
    protected function impersonateUser(User $user)
    {
        $currentToken = $this->tokenStorage->getToken();
        if (!$currentToken instanceof OrganizationContextTokenInterface) {
            throw new \UnexpectedValueException('The current security token must be aware of the organization.');
        }

        $organization = $currentToken->getOrganizationContext();

        // check if new user has access to the current organization
        if (!$user->hasOrganization($organization)) {
            throw new AccessDeniedException();
        }

        $this->tokenStorage->setToken(
            new ImpersonationToken($user, $organization, $user->getRoles())
        );

        return $currentToken;
    }

    /**
     * Switches the security context to the previous security token
     *
     * @param TokenInterface|null $originalToken
     */
    protected function undoImpersonation(TokenInterface $originalToken = null)
    {
        if ($originalToken) {
            $this->tokenStorage->setToken($originalToken);
        }
    }
}
