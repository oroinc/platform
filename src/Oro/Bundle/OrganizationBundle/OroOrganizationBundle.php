<?php

namespace Oro\Bundle\OrganizationBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\OrganizationBundle\DependencyInjection\Compiler\OwnerDeletionManagerPass;

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
