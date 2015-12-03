<?php

namespace Oro\Bundle\LocaleBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\AddDateTimeFormatConverterCompilerPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroLocaleBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddDateTimeFormatConverterCompilerPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}
