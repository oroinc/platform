<?php

namespace Oro\Bundle\UserBundle\Entity\Manager;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclClassInfo;
use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionSelector;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

use Oro\Bundle\UserBundle\Entity\User;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class UserPermissionApiEntityManager extends ApiEntityManager
{
    /** @var SecurityContext */
    protected $securityContext;

    /** @var AclExtensionSelector */
    protected $aclSelector;

    /** @var TokenInterface|null */
    protected $tempToken;

    public function __construct(SecurityContext $securityContext, AclExtensionSelector $aclSelector)
    {
        $this->aclSelector     = $aclSelector;
        $this->securityContext = $securityContext;
        $this->tempToken       = $securityContext->getToken();
    }

    /**
     * Gets permissions for user $user
     *
     * @param User  $user
     * @param array $entities
     *
     * @return array
     *
     * @throws InvalidDomainObjectException
     */
    public function getData(User $user, array $entities = [])
    {
        foreach ($entities as $key => $entity) {
            $entity[$key] = $this->resolveEntityClass($entity);
        }

        $extension   = $this->aclSelector->select($user);
        $classes     = $extension->getClasses();
        $permissions = $extension->getPermissions();

        if (!empty($entities)) {
            /** @var AclClassInfo[] $classes */
            $classes = array_filter($classes, function (AclClassInfo $class) use ($entities) {
                return in_array($class->getClassName(), $entities);
            });
        }

        $this->setTokenForUser($user);
        $result = [];
        foreach ($classes as $class) {
            $data = [
                'entity'      => $class->getClassName(),
                'permissions' => []
            ];
            foreach ($permissions as $permission) {
                $entity                           = 'entity:' . $class->getClassName();
                $data['permissions'][$permission] = $this->securityContext->isGranted($permission, $entity);
            }
            $result[] = $data;
        }

        $this->resetToken();

        return $result;
    }

    /**
     * Sets token for user $user
     *
     * @param User $user
     *
     * @throws \Exception
     */
    protected function setTokenForUser(User $user)
    {
        if ($user->getOrganization() === null) {
            throw new \Exception('User should have active organization assigned.');
        }
        $token = new UsernamePasswordOrganizationToken(
            $user,
            $user->getUsername(),
            'main',
            $user->getOrganization(),
            $user->getRoles()
        );
        $this->securityContext->setToken($token);
    }

    /**
     * Resets token for current authorized user
     */
    protected function resetToken()
    {
        $this->securityContext->setToken($this->tempToken);
    }
}
