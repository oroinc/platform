<?php

namespace Oro\Bundle\ApiBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler;
use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Bundle\InstallerBundle\CommandExecutor;
use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;
use Oro\Component\ChainProcessor\DependencyInjection\LoadApplicableCheckersCompilerPass;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceWithHandlerCompilerPass;
use Oro\Component\DependencyInjection\Compiler\PriorityTaggedServiceViaAddMethodCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The ApiBundle bundle class.
 */
class OroApiBundle extends Bundle
{
    use Compiler\ApiTaggedServiceTrait;

    /** @var KernelInterface */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\ProcessorBagCompilerPass());
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.filter_names_registry',
            'oro.api.filter_names'
        ));
        $container->addCompilerPass(new Compiler\SimpleFilterFactoryCompilerPass());
        $container->addCompilerPass(new Compiler\FormCompilerPass());
        $container->addCompilerPass(new Compiler\DataTransformerCompilerPass());
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.object_normalizer_registry',
            'oro.api.object_normalizer',
            function (array $attributes, string $serviceId, string $tagName): array {
                return [
                    $this->getRequiredAttribute($attributes, 'class', $serviceId, $tagName)
                ];
            }
        ));
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.entity_id_transformer_registry',
            'oro.api.entity_id_transformer'
        ));
        $container->addCompilerPass(new Compiler\EntityIdResolverCompilerPass());
        $container->addCompilerPass(new Compiler\EntityAliasCompilerPass());
        $container->addCompilerPass(new PriorityTaggedServiceViaAddMethodCompilerPass(
            'oro_api.entity_exclusion_provider.shared',
            'oro_entity.exclusion_provider.api',
            'addProvider'
        ));
        $container->addCompilerPass(new Compiler\QueryExpressionCompilerPass());
        $container->addCompilerPass(new Compiler\ApiDocLogoutCompilerPass());
        $container->addCompilerPass(new Compiler\SecurityFirewallCompilerPass());
        $container->addCompilerPass(new Compiler\DocumentBuilderCompilerPass());
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.error_completer_registry',
            'oro.api.error_completer'
        ));
        $container->addCompilerPass(new Compiler\ResourceDocParserCompilerPass());
        $container->addCompilerPass(new Compiler\ResourcesCacheWarmerCompilerPass());
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.entity_serializer.query_modifier_registry',
            'oro.api.query_modifier'
        ));
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.entity_serializer.mandatory_field_provider_registry',
            'oro.api.mandatory_field_provider'
        ));
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.api_doc.documentation_provider',
            'oro.api.documentation_provider'
        ));
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.rest.routes_registry',
            'oro.api.rest_routes'
        ));
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.complete_definition_helper.custom_data_type',
            'oro.api.custom_data_type_completer'
        ));
        $container->addCompilerPass(new Compiler\ChunkSizeProviderCompilerPass());
        $container->addCompilerPass(new Compiler\CleanupAsyncOperationCompilerPass());
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.batch.file_splitter_registry',
            'oro.api.batch.file_splitter'
        ));
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.batch.chunk_file_classifier_registry',
            'oro.api.batch.chunk_file_classifier'
        ));
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.batch.data_encoder_registry',
            'oro.api.batch.data_encoder'
        ));
        $container->addCompilerPass(new Compiler\RequestTypeDependedTaggedServiceCompilerPass(
            'oro_api.batch.include_accessor_registry',
            'oro.api.batch.include_accessor'
        ));
        $container->addCompilerPass(new PriorityNamedTaggedServiceWithHandlerCompilerPass(
            'oro_api.batch.flush_data_handler_factory_registry',
            'oro.api.batch.flush_data_handler_factory',
            function (array $attributes, string $serviceId): array {
                return [$serviceId, $this->getAttribute($attributes, 'class')];
            }
        ));
        $container->addCompilerPass(new LoadApplicableCheckersCompilerPass(
            'oro_api.processor_bag',
            'oro.api.processor.applicable_checker'
        ));
        $container->addCompilerPass(
            new CleanUpProcessorsCompilerPass(
                'oro_api.simple_processor_registry',
                DependencyInjectionUtil::PROCESSOR_TAG,
                'oro_api.simple_processor_registry.inner'
            ),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        $container->addCompilerPass(
            new Compiler\ApiDocCompilerPass(),
            PassConfig::TYPE_BEFORE_OPTIMIZATION,
            -250
        );
        $container->addCompilerPass(
            new Compiler\RemoveConfigParameterCompilerPass(),
            PassConfig::TYPE_AFTER_REMOVING
        );

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(
                DoctrineOrmMappingsPass::createAnnotationMappingDriver(
                    ['Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity'],
                    [$this->getPath() . '/Tests/Functional/Environment/Entity']
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        // Warms up API caches here to detect misconfiguration (e.g. duplicated entity aliases) as early as possible
        // to avoid ORM metadata exceptions like "The target entity Extend\Entity\... cannot be found in ...".
        // Do it only if extended entity proxies are ready.
        // Skip for "cache:clear" and "cache:warmup" commands to avoid premature warming up caches;
        // in this case caches are warmed up by cache warmers
        // Skip the following commands:
        // * oro:install
        // * oro:platform:update
        // * oro:migration:load
        // * oro:entity-config:update
        // * oro:entity-config:cache:*
        // * oro:entity-extend:update-config
        // * oro:entity-extend:migration:update-config
        // * oro:entity-extend:cache:*
        // * doctrine:database:create
        // to avoid "Class Extend\Entity\... does not exist" exceptions for case when custom entities are created
        // via migrations and has some configuration in "Resources/config/oro/api.yml"
        if ($this->kernel->isDebug()
            && ExtendClassLoadingUtils::aliasesExist($this->kernel->getCacheDir())
            && !CommandExecutor::isCurrentCommand('cache:clear')
            && !CommandExecutor::isCurrentCommand('cache:warmup')
            && !CommandExecutor::isCurrentCommand('oro:install')
            && !CommandExecutor::isCurrentCommand('oro:platform:update')
            && !CommandExecutor::isCurrentCommand('oro:migration:load')
            && !CommandExecutor::isCurrentCommand('oro:entity-config:update')
            && !CommandExecutor::isCurrentCommand('oro:entity-config:cache:', true)
            && !CommandExecutor::isCurrentCommand('oro:entity-extend:update-config')
            && !CommandExecutor::isCurrentCommand('oro:entity-extend:migration:update-config')
            && !CommandExecutor::isCurrentCommand('oro:entity-extend:cache:', true)
            && !CommandExecutor::isCurrentCommand('doctrine:database:create')
        ) {
            $this->getCacheManager()->warmUpDirtyCaches();
        }
    }

    /**
     * @return CacheManager
     */
    private function getCacheManager()
    {
        return $this->kernel->getContainer()->get('oro_api.cache_manager');
    }
}
