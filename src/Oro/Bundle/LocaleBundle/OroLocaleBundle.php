<?php

namespace Oro\Bundle\LocaleBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\AddDateTimeFormatConverterCompilerPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;

class OroLocaleBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->registerResource(
            $this->getName(),
            [
                'Resources/config/oro/name_format.yml',
                'Resources/config/oro/address_format.yml',
                'Resources/config/oro/locale_data.yml',
                'Resources/config/oro/currency_data.yml'
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddDateTimeFormatConverterCompilerPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}
