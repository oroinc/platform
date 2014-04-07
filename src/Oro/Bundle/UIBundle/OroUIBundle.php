<?php

namespace Oro\Bundle\UIBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\TwigConfigurationPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\PlaceholderFilterCompilerPass;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class OroUIBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->addResourceLoader(
            $this->getName(),
            new YamlCumulativeFileLoader('Resources/config/placeholders.yml')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TwigConfigurationPass());
        $container->addCompilerPass(new PlaceholderFilterCompilerPass());
    }
}
