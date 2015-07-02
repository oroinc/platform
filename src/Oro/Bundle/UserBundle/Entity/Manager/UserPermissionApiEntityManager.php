<?php

namespace Oro\Bundle\UserBundle\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Authentication\Token\ImpersonationToken;
use Oro\Bundle\SecurityBundle\Authentication\Token\OrganizationContextTokenInterface;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\UserBundle\Entity\User;

class UserPermissionApiEntityManager extends ApiEntityManager
{
    /** @var SecurityContext */
    protected $securityContext;

    /** @var AclExtensionSelector */
    protected $aclSelector;

    /**
     * @param string               $class
     * @param ObjectManager        $om
     * @param SecurityContext      $securityContext
     * @param AclExtensionSelector $aclSelector
     */
    public function __construct(
        $class,
        ObjectManager $om,
        SecurityContext $securityContext,
        AclExtensionSelector $aclSelector
    ) {
        parent::__construct($class, $om);
        $this->aclSelector     = $aclSelector;
        $this->securityContext = $securityContext;
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
                    if ($this->securityContext->isGranted($permission, $oid)) {
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
        $currentToken = $this->securityContext->getToken();
        if (!$currentToken instanceof OrganizationContextTokenInterface) {
            throw new \UnexpectedValueException('The current security token must be aware of the organization.');
        }

        $organization = $currentToken->getOrganizationContext();

        // check if new user has access to the current organization
        if (!$user->hasOrganization($organization)) {
            throw new AccessDeniedException();
        }

        $this->securityContext->setToken(
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
            $this->securityContext->setToken($originalToken);
        }
    }
}
