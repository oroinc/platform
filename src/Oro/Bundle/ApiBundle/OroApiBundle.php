<?php

namespace Oro\Bundle\ApiBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler;
use Oro\Bundle\ApiBundle\Provider\CacheManager;
use Oro\Bundle\ApiBundle\Util\DependencyInjectionUtil;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendClassLoadingUtils;
use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;
use Oro\Component\ChainProcessor\DependencyInjection\LoadApplicableCheckersCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * The ApiBundle bundle class.
 */
class OroApiBundle extends Bundle
{
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
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\ProcessorBagCompilerPass());
        $container->addCompilerPass(new Compiler\FilterNamesCompilerPass());
        $container->addCompilerPass(new Compiler\FilterFactoryCompilerPass());
        $container->addCompilerPass(new Compiler\FormCompilerPass());
        $container->addCompilerPass(new Compiler\DataTransformerCompilerPass());
        $container->addCompilerPass(new Compiler\EntityIdTransformerCompilerPass());
        $container->addCompilerPass(new Compiler\EntityIdResolverCompilerPass());
        $container->addCompilerPass(new Compiler\EntityAliasCompilerPass());
        $container->addCompilerPass(new Compiler\ExclusionProviderCompilerPass());
        $container->addCompilerPass(new Compiler\ExceptionTextExtractorCompilerPass());
        $container->addCompilerPass(new Compiler\ConstraintTextExtractorCompilerPass());
        $container->addCompilerPass(new Compiler\QueryExpressionCompilerPass());
        $container->addCompilerPass(new Compiler\ApiDocLogoutCompilerPass());
        $container->addCompilerPass(new Compiler\SecurityFirewallCompilerPass());
        $container->addCompilerPass(new Compiler\DocumentBuilderCompilerPass());
        $container->addCompilerPass(new Compiler\ErrorCompleterCompilerPass());
        $container->addCompilerPass(new Compiler\ResourceDocParserCompilerPass());
        $container->addCompilerPass(new Compiler\ResourcesCacheWarmerCompilerPass());
        $container->addCompilerPass(new Compiler\QueryModifierCompilerPass());
        $container->addCompilerPass(new Compiler\MandatoryFieldProviderCompilerPass());
        $container->addCompilerPass(new Compiler\DocumentationProviderCompilerPass());
        $container->addCompilerPass(new Compiler\RestRoutesCompilerPass());
        $container->addCompilerPass(
            new LoadApplicableCheckersCompilerPass('oro_api.processor_bag', 'oro.api.processor.applicable_checker')
        );
        $container->addCompilerPass(
            new CleanUpProcessorsCompilerPass(
                'oro_api.simple_processor_factory',
                DependencyInjectionUtil::PROCESSOR_TAG
            ),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        $container->addCompilerPass(
            new Compiler\ApiDocCompilerPass(),
            PassConfig::TYPE_BEFORE_REMOVING
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
        // warms up API caches here to detect misconfiguration (e.g. duplicated entity aliases) as early as possible
        // to avoid ORM metadata exceptions like "The target entity Extend\Entity\... cannot be found in ..."
        // do it only if extended entity proxies are ready
        if ($this->kernel->isDebug() && ExtendClassLoadingUtils::aliasesExist($this->kernel->getCacheDir())) {
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
