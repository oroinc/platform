<?php

namespace Oro\Bundle\LocaleBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The LocaleBundle bundle class.
 */
class OroLocaleBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_locale.format_converter.date_time.registry',
            'oro_locale.format_converter.date_time',
            'alias'
        ));
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new DefaultFallbackExtensionPass([
            'Oro\Bundle\LocaleBundle\Entity\Localization' => [
                'title' => 'titles'
            ]
        ]));
    }
}
