<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AclConfigurationPass implements CompilerPassInterface
{
    const NEW_ACL_PROVIDER = 'oro_security.acl.provider';
    const NEW_ACL_DBAL_PROVIDER_CLASS = 'oro_security.acl.dbal.provider.class';

    const DEFAULT_ACL_VOTER = 'security.acl.voter.basic_permissions';
    const DEFAULT_ACL_DBAL_PROVIDER_CLASS = 'security.acl.dbal.provider.class';

    const ACL_EXTENSION_SELECTOR = 'oro_security.acl.extension_selector';
    const ACL_EXTENSION_TAG = 'oro_security.acl.extension';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->configureAclExtensionSelector($container);
        $this->configureDefaultAclProvider($container);
        $this->configureDefaultAclVoter($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureAclExtensionSelector(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::ACL_EXTENSION_SELECTOR)) {
            $selectorDef = $container->getDefinition(self::ACL_EXTENSION_SELECTOR);
            $extensions = $this->loadAclExtensions($container);
            foreach ($extensions as $extensionServiceId) {
                $selectorDef->addMethodCall('addAclExtension', array(new Reference($extensionServiceId)));
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configureDefaultAclProvider(ContainerBuilder $container)
    {
        if ($container->hasParameter(self::DEFAULT_ACL_DBAL_PROVIDER_CLASS)) {
            if ($container->hasParameter(self::NEW_ACL_DBAL_PROVIDER_CLASS)) {
                // change implementation of ACL DBAL provider
                $container->setParameter(
                    self::DEFAULT_ACL_DBAL_PROVIDER_CLASS,
                    $container->getParameter(self::NEW_ACL_DBAL_PROVIDER_CLASS)
                );
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function configureDefaultAclVoter(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::DEFAULT_ACL_VOTER)) {
            $voterDef = $container->getDefinition(self::DEFAULT_ACL_VOTER);
            // substitute the ACL Provider and set the default ACL Provider as a base provider for new ACL Provider
            if ($container->hasDefinition(self::NEW_ACL_PROVIDER)) {
                $newProviderDef = $container->getDefinition(self::NEW_ACL_PROVIDER);
                $newProviderDef->addMethodCall('setBaseAclProvider', array($voterDef->getArgument(0)));
                $voterDef->replaceArgument(0, new Reference(self::NEW_ACL_PROVIDER));
            }
        }
    }

    /**
     * Load ACL extensions and sort them by priority.
     *
     * @param  ContainerBuilder $container
     * @return array
     */
    protected function loadAclExtensions(ContainerBuilder $container)
    {
        $extensions = array();
        foreach ($container->findTaggedServiceIds(self::ACL_EXTENSION_TAG) as $id => $attributes) {
            $priority = 0;
            foreach ($attributes as $attr) {
                if (isset($attr['priority'])) {
                    $priority = (int) $attr['priority'];
                    break;
                }
            }

            $extensions[] = array('id' => $id, 'priority' => $priority);
        }
        usort(
            $extensions,
            function ($a, $b) {
                return $a['priority'] == $b['priority']
                    ? 0
                    : ($a['priority'] < $b['priority']) ? -1 : 1;
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
