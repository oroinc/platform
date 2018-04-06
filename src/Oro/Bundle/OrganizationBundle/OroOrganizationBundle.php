<?php

namespace Oro\Bundle\OrganizationBundle;

use Oro\Bundle\OrganizationBundle\DependencyInjection\Compiler\OwnerDeletionManagerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroOrganizationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OwnerDeletionManagerPass());
    }
}
