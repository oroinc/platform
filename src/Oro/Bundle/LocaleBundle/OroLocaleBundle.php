<?php

namespace Oro\Bundle\LocaleBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\AddDateTimeFormatConverterCompilerPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\CurrentLocalizationPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;

class OroLocaleBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddDateTimeFormatConverterCompilerPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new CurrentLocalizationPass());
        $container->addCompilerPass(new DefaultFallbackExtensionPass([
            'Oro\Bundle\LocaleBundle\Entity\Localization' => ['title' => 'titles']
        ]));
    }
}
