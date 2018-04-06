<?php

namespace Oro\Bundle\SecurityBundle\Twig;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Voter\FieldVote;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OroSecurityExtension extends \Twig_Extension
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    protected function getAuthorizationChecker()
    {
        return $this->container->get('security.authorization_checker');
    }

    /**
     * @return TokenAccessorInterface
     */
    protected function getTokenAccessor()
    {
        return $this->container->get('oro_security.token_accessor');
    }

    /**
     * @return PermissionManager
     */
    protected function getPermissionManager()
    {
        return $this->container->get('oro_security.acl.permission_manager');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'resource_granted',
                [$this, 'checkResourceIsGranted'],
                ['deprecated' => true, 'alternative' => 'is_granted']
            ),
            new \Twig_SimpleFunction('get_enabled_organizations', [$this, 'getOrganizations']),
            new \Twig_SimpleFunction('get_current_organization', [$this, 'getCurrentOrganization']),
            new \Twig_SimpleFunction('acl_permission', [$this, 'getPermission']),
        ];
    }

    /**
     * Check if ACL resource is granted for current user
     *
     * @param string|string[] $attributes Can be a role name(s), permission name(s), an ACL annotation id
     *                                    or something else, it depends on registered security voters
     * @param mixed           $object     A domain object, object identity or object identity descriptor (id:type)
     * @param string          $fieldName  Field name in case if Field ACL check should be used
     *
     * @return bool
     * @deprecated since 2.3. Use Symfony "is_granted" function instead
     */
    public function checkResourceIsGranted($attributes, $object = null, $fieldName = null)
    {
        if ($fieldName) {
            return $this->getAuthorizationChecker()->isGranted($attributes, new FieldVote($object, $fieldName));
        }

        return $this->getAuthorizationChecker()->isGranted($attributes, $object);
    }

    /**
     * Get list with all enabled organizations for current user
     *
     * @return Organization[]
     */
    public function getOrganizations()
    {
        $user = $this->getTokenAccessor()->getUser();
        if (!$user instanceof User) {
            return [];
        }

        return $user->getOrganizations(true)->toArray();
    }

    /**
     * Returns current organization
     *
     * @return Organization|null
     */
    public function getCurrentOrganization()
    {
        return $this->getTokenAccessor()->getOrganization();
    }

    /**
     * @param AclPermission $aclPermission
     *
     * @return Permission
     */
    public function getPermission(AclPermission $aclPermission)
    {
        return $this->getPermissionManager()->getPermissionByName($aclPermission->getName());
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'oro_security_extension';
    }
}
