<?php

namespace Oro\Bundle\UIBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\UIBundle\DependencyInjection\Compiler\TwigConfigurationPass;
use Oro\Bundle\UIBundle\DependencyInjection\Compiler\PlaceholderFilterCompilerPass;

use Oro\Component\Config\CumulativeResourceManager;

class OroUIBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->registerResource(
            $this->getName(),
            'Resources/config/placeholders.yml'
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
