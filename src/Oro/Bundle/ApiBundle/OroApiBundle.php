<?php

namespace Oro\Bundle\ApiBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;
use Oro\Component\ChainProcessor\DependencyInjection\LoadApplicableCheckersCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler;

class OroApiBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\ConfigurationCompilerPass());
        $container->addCompilerPass(new Compiler\DataTransformerConfigurationCompilerPass());
        $container->addCompilerPass(new Compiler\EntityIdTransformerConfigurationCompilerPass());
        $container->addCompilerPass(new Compiler\EntityAliasesConfigurationCompilerPass());
        $container->addCompilerPass(new Compiler\ExclusionProviderConfigurationCompilerPass());
        $container->addCompilerPass(new Compiler\ExceptionTextExtractorConfigurationCompilerPass());
        $container->addCompilerPass(new Compiler\ConstraintTextExtractorConfigurationCompilerPass());
        $container->addCompilerPass(new Compiler\QueryExpressionCompilerPass());
        $container->addCompilerPass(
            new LoadApplicableCheckersCompilerPass('oro_api.processor_bag', 'oro.api.processor.applicable_checker')
        );
        $container->addCompilerPass(
            new CleanUpProcessorsCompilerPass('oro_api.simple_processor_factory', 'oro.api.processor'),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        $container->addCompilerPass(
            new Compiler\ApiDocConfigurationCompilerPass(),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        $container->addCompilerPass(new Compiler\ApiSecurityFirewallCompilerPass());
        $container->addCompilerPass(new Compiler\DocumentBuilderConfigurationCompilerPass());
        $container->addCompilerPass(new Compiler\ErrorCompleterConfigurationCompilerPass());

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
