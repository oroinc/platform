<?php

namespace Oro\Bundle\SecurityBundle\Twig;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\Permission\PermissionManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Entity\Permission;
use Oro\Bundle\SecurityBundle\Form\Type\SwitchOrganizationType;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;
use Oro\Bundle\UserBundle\Entity\User;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
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
    private ContainerInterface $container;
    private ?PermissionManager $permissionManager = null;
    private ?UriSecurityHelper $uriSecurityHelper = null;
    private ?TokenAccessorInterface $tokenAccessor = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('get_enabled_organizations', [$this, 'getOrganizations']),
            new TwigFunction('get_current_organization', [$this, 'getCurrentOrganization']),
            new TwigFunction('acl_permission', [$this, 'getPermission']),
            new TwigFunction('is_authenticated', [$this, 'isAuthenticated']),
            new TwigFunction('get_organization_selector_form', [$this, 'getOrganizationSelectorForm']),
            new TwigFunction('get_user_organizations_count', [$this, 'getUserOrganizationsCount']),
        ];
    }

    #[\Override]
    public function getFilters()
    {
        return [
            new TwigFilter('strip_dangerous_protocols', [$this, 'stripDangerousProtocols']),
        ];
    }

    /**
     * Get list with all enabled organizations for current user
     *
     * @return array [['id' => organization id, 'name' => organization  name], ...]
     */
    public function getOrganizations()
    {
        $user = $this->getTokenAccessor()->getUser();
        if (!$user instanceof User) {
            return [];
        }

        return $this->formatUserOrganizations($user->getOrganizations(true)->toArray());
    }

    public function getUserOrganizationsCount(): int
    {
        $user = $this->getTokenAccessor()->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        return $this->container->get('doctrine')
            ->getManagerForClass(Organization::class)
            ->getRepository(Organization::class)
            ->getUserOrganizationsCount($user);
    }

    public function getOrganizationSelectorForm(): FormView
    {
        $form = $this->container->get(FormFactoryInterface::class)
            ->createNamedBuilder('', FormType::class, [], ['csrf_protection' => false])
            ->add(
                'organization',
                SwitchOrganizationType::class
            )
            ->setMethod('GET')
            ->getForm()
            ->createView();

        return $form;
    }

    public function getCurrentOrganization(): ?Organization
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
     * @param Organization[] $userOrganizations
     *
     * @return array [['id' => organization id, 'name' => organization  name], ...]
     */
    protected function formatUserOrganizations(array $userOrganizations): array
    {
        if (!$userOrganizations) {
            return [];
        }

        $result = [];
        foreach ($userOrganizations as $org) {
            $result[] = ['id' => $org->getId(), 'name' => $org->getName()];
        }

        return $result;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_security.util.uri_security_helper' => UriSecurityHelper::class,
            'oro_security.acl.permission_manager' => PermissionManager::class,
            TokenAccessorInterface::class,
            FormFactoryInterface::class,
            'doctrine' => ManagerRegistry::class,
        ];
    }

    public function isAuthenticated(): bool
    {
        $user = $this->getTokenAccessor()->getUser();

        return null !== $user && !$user instanceof CustomerVisitor;
    }

    private function getPermissionManager(): PermissionManager
    {
        if (null === $this->permissionManager) {
            $this->permissionManager = $this->container->get('oro_security.acl.permission_manager');
        }

        return $this->permissionManager;
    }

    private function getUriSecurityHelper(): UriSecurityHelper
    {
        if (null === $this->uriSecurityHelper) {
            $this->uriSecurityHelper = $this->container->get('oro_security.util.uri_security_helper');
        }

        return $this->uriSecurityHelper;
    }

    private function getTokenAccessor(): TokenAccessorInterface
    {
        if (null === $this->tokenAccessor) {
            $this->tokenAccessor = $this->container->get(TokenAccessorInterface::class);
        }

        return $this->tokenAccessor;
    }
}
