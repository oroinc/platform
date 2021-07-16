<?php

namespace Oro\Bundle\SecurityBundle\Twig;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides Twig functions and filters:
 *   - get_enabled_organizations
 *   - get_current_organization
 *   - acl_permission
 *   - strip_dangerous_protocols
 */
class OroSecurityExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return AuthorizationCheckerInterface
     */
    protected function getAuthorizationChecker()
    {
        return $this->container->get(AuthorizationCheckerInterface::class);
    }

    /**
     * @return TokenAccessorInterface
     */
    protected function getTokenAccessor()
    {
        return $this->container->get(TokenAccessorInterface::class);
    }

    /**
     * @return PermissionManager
     */
    protected function getPermissionManager()
    {
        return $this->container->get(PermissionManager::class);
    }

    private function getUriSecurityHelper(): UriSecurityHelper
    {
        return $this->container->get(UriSecurityHelper::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('get_enabled_organizations', [$this, 'getOrganizations']),
            new TwigFunction('get_current_organization', [$this, 'getCurrentOrganization']),
            new TwigFunction('acl_permission', [$this, 'getPermission']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('strip_dangerous_protocols', [$this, 'stripDangerousProtocols']),
        ];
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
     * @param mixed $uri
     *
     * @return string
     */
    public function stripDangerousProtocols($uri): string
    {
        return $this->getUriSecurityHelper()->stripDangerousProtocols((string) $uri);
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            AuthorizationCheckerInterface::class,
            TokenAccessorInterface::class,
            PermissionManager::class,
            UriSecurityHelper::class,
        ];
    }
}
