<?php

namespace Oro\Bundle\QueryDesignerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\QueryDesignerBundle\DependencyInjection\Compiler\ConfigurationPass;

use Oro\Component\Config\CumulativeResourceManager;

class OroQueryDesignerBundle extends Bundle
{
    /**
     * Constructor
     */
    public function __construct()
    {
        CumulativeResourceManager::getInstance()->registerResource(
            $this->getName(),
            'Resources/config/query_designer.yml'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationPass());
    }
}
