<?php

namespace Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers all entity alias providers that are used only in API.
 */
class EntityAliasCompilerPass implements CompilerPassInterface
{
    use ApiTaggedServiceTrait;

    private const ENTITY_ALIAS_RESOLVER_REGISTRY_SERVICE_ID = 'oro_api.entity_alias_resolver_registry';

    private const ALIAS_PROVIDER_TAG_NAME = 'oro_entity.alias_provider';
    private const CLASS_PROVIDER_TAG_NAME = 'oro_entity.class_provider';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $classProviders = $this->getProviders($container, self::CLASS_PROVIDER_TAG_NAME);
        $aliasProviders = $this->getProviders($container, self::ALIAS_PROVIDER_TAG_NAME);
        $resolvers = $container->getDefinition(self::ENTITY_ALIAS_RESOLVER_REGISTRY_SERVICE_ID)
            ->getArgument('$entityAliasResolvers');
        foreach ($resolvers as $resolver) {
            $loaderServiceId = (string)$container->getDefinition($resolver[0])->getArgument(0);
            $this->addProviders($container, $loaderServiceId, 0, $classProviders);
            $this->addProviders($container, $loaderServiceId, 1, $aliasProviders);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $tagName
     *
     * @return Reference[]
     */
    private function getProviders(ContainerBuilder $container, string $tagName): array
    {
        $providers = [];
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $id => $tags) {
            $providers[$this->getPriorityAttribute($tags[0])][] = new Reference($id);
        }
        if ($providers) {
            $providers = $this->sortByPriorityAndFlatten($providers);
        }

        return $providers;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $loaderServiceId
     * @param int              $argumentIndex
     * @param Reference[]      $providers
     */
    private function addProviders(
        ContainerBuilder $container,
        string $loaderServiceId,
        int $argumentIndex,
        array $providers
    ): void {
        $loaderDef = $container->getDefinition($loaderServiceId);

        $existingArgument = $loaderDef->getArgument($argumentIndex);
        if (!$existingArgument instanceof IteratorArgument) {
            throw new InvalidArgumentException(sprintf(
                'Invalid definition for service "%s": argument %d should be "%s", "%s" passed.',
                $loaderServiceId,
                $argumentIndex,
                IteratorArgument::class,
                get_debug_type($existingArgument)
            ));
        }

        $loaderDef->replaceArgument(
            $argumentIndex,
            new IteratorArgument(array_merge($existingArgument->getValues(), $providers))
        );
    }
}
