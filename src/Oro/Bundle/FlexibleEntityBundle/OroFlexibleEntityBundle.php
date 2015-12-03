<?php
namespace Oro\Bundle\FlexibleEntityBundle;

use Oro\Bundle\FlexibleEntityBundle\DependencyInjection\Compiler\AddAttributeTypeCompilerPass;
use Oro\Bundle\FlexibleEntityBundle\DependencyInjection\Compiler\AddManagerCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Flexible entity bundle
 *
 *
 */
class OroFlexibleEntityBundle extends Bundle
{

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AddManagerCompilerPass());
        $container->addCompilerPass(new AddAttributeTypeCompilerPass());
    }
}
