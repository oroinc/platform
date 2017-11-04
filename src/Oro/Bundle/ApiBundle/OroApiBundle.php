<?php

namespace Oro\Bundle\ApiBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\ChainProcessor\DependencyInjection\CleanUpProcessorsCompilerPass;
use Oro\Component\ChainProcessor\DependencyInjection\LoadApplicableCheckersCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ApiDocConfigurationCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ApiSecurityFirewallCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ConfigurationCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ConstraintTextExtractorConfigurationCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\DataTransformerConfigurationCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\EntityAliasesConfigurationCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\EntityIdTransformerConfigurationCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ExceptionTextExtractorConfigurationCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\ExclusionProviderConfigurationCompilerPass;
use Oro\Bundle\ApiBundle\DependencyInjection\Compiler\QueryExpressionCompilerPass;

class OroApiBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationCompilerPass());
        $container->addCompilerPass(new DataTransformerConfigurationCompilerPass());
        $container->addCompilerPass(new EntityIdTransformerConfigurationCompilerPass());
        $container->addCompilerPass(new EntityAliasesConfigurationCompilerPass());
        $container->addCompilerPass(new ExclusionProviderConfigurationCompilerPass());
        $container->addCompilerPass(new ExceptionTextExtractorConfigurationCompilerPass());
        $container->addCompilerPass(new ConstraintTextExtractorConfigurationCompilerPass());
        $container->addCompilerPass(new QueryExpressionCompilerPass());
        $container->addCompilerPass(
            new LoadApplicableCheckersCompilerPass('oro_api.processor_bag', 'oro.api.processor.applicable_checker')
        );
        $container->addCompilerPass(
            new CleanUpProcessorsCompilerPass('oro_api.simple_processor_factory', 'oro.api.processor'),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        $container->addCompilerPass(
            new ApiDocConfigurationCompilerPass(),
            PassConfig::TYPE_BEFORE_REMOVING
        );
        $container->addCompilerPass(
            new ApiSecurityFirewallCompilerPass()
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
