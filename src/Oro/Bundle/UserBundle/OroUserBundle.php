<?php

namespace Oro\Bundle\UserBundle;

use Oro\Bundle\UserBundle\DependencyInjection\Compiler\PrivilegeCategoryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Container extension for OroUserBundle.
 */
class OroUserBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new PrivilegeCategoryPass());
    }
}
