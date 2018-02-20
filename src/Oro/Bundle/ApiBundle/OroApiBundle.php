<?php

namespace Oro\Bundle\ApiBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler;
use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;
use Oro\Component\ChainProcessor\DependencyInjection\LoadApplicableCheckersCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The ApiBundle bundle class.
 */
class OroApiBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\ProcessorBagCompilerPass());
        $container->addCompilerPass(new Compiler\FilterFactoryCompilerPass());
        $container->addCompilerPass(new Compiler\FormCompilerPass());
        $container->addCompilerPass(new Compiler\DataTransformerCompilerPass());
        $container->addCompilerPass(new Compiler\EntityIdTransformerCompilerPass());
        $container->addCompilerPass(new Compiler\EntityAliasCompilerPass());
        $container->addCompilerPass(new Compiler\ExclusionProviderCompilerPass());
        $container->addCompilerPass(new Compiler\ExceptionTextExtractorCompilerPass());
        $container->addCompilerPass(new Compiler\ConstraintTextExtractorCompilerPass());
        $container->addCompilerPass(new Compiler\QueryExpressionCompilerPass());
        $container->addCompilerPass(new Compiler\SecurityFirewallCompilerPass());
        $container->addCompilerPass(new Compiler\DocumentBuilderCompilerPass());
        $container->addCompilerPass(new Compiler\ErrorCompleterCompilerPass());
        $container->addCompilerPass(new Compiler\ResourcesCacheWarmerCompilerPass());
        $container->addCompilerPass(
            new LoadApplicableCheckersCompilerPass('oro_api.processor_bag', 'oro.api.processor.applicable_checker')
        );
        $container->addCompilerPass(
            new CleanUpProcessorsCompilerPass('oro_api.simple_processor_factory', 'oro.api.processor'),
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
}
