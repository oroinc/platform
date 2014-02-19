<?php

namespace Oro\Bundle\OrganizationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Oro\Bundle\OrganizationBundle\DependencyInjection\Compiler\OwnerDeletionManagerPass;

class OroOrganizationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new OwnerDeletionManagerPass());
    }
}
