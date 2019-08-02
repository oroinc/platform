<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\Acl\Dbal\MutableAclProvider;
use Oro\Bundle\SecurityBundle\Acl\Domain\SecurityIdentityRetrievalStrategy;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Reconfigure ACL related services.
 */
class AclConfigurationPass implements CompilerPassInterface
{
    private const ACL_EXTENSION_TAG = 'oro_security.acl.extension';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->substituteSecurityIdentityStrategy($container);
        $this->configureAclExtensionSelector($container);
        $this->configureDefaultAclProvider($container);
        $this->configureDefaultAclVoter($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function substituteSecurityIdentityStrategy(ContainerBuilder $container)
    {
        $container->setDefinition(
            'security.acl.security_identity_retrieval_strategy',
            new Definition(SecurityIdentityRetrievalStrategy::class)
        );
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureAclExtensionSelector(ContainerBuilder $container)
    {
        $selectorDef = $container->getDefinition('oro_security.acl.extension_selector');
        $extensions = $this->loadAclExtensions($container);
        foreach ($extensions as $extensionServiceId) {
            $selectorDef->addMethodCall('addAclExtension', [new Reference($extensionServiceId)]);
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configureDefaultAclProvider(ContainerBuilder $container)
    {
        $container->getDefinition('security.acl.dbal.provider')
            ->setClass(MutableAclProvider::class);
    }

    /**
     * @param ContainerBuilder $container
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function configureDefaultAclVoter(ContainerBuilder $container)
    {
        $voterDef = $container->getDefinition('security.acl.voter.basic_permissions');
        // substitute the ACL Provider and set the default ACL Provider as a base provider for new ACL Provider
        $newProviderId = 'oro_security.acl.provider';
        $newProviderDef = $container->getDefinition($newProviderId);
        $newProviderDef->addMethodCall('setBaseAclProvider', [$voterDef->getArgument(0)]);
        $voterDef->replaceArgument(0, new Reference($newProviderId));
    }

    /**
     * Load ACL extensions and sort them by priority.
     *
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function loadAclExtensions(ContainerBuilder $container)
    {
        $extensions = [];
        foreach ($container->findTaggedServiceIds(self::ACL_EXTENSION_TAG) as $id => $attributes) {
            $priority = 0;
            foreach ($attributes as $attr) {
                if (isset($attr['priority'])) {
                    $priority = (int)$attr['priority'];
                    break;
                }
            }
            $extensions[] = ['id' => $id, 'priority' => $priority];
        }
        usort(
            $extensions,
            function ($a, $b) {
                return $a['priority'] <=> $b['priority'];
            }
        );

        return array_map(
            function ($el) {
                return $el['id'];
            },
            $extensions
        );
    }
}
