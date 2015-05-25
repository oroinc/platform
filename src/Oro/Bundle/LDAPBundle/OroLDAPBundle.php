<?php

namespace Oro\Bundle\LDAPBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LDAPBundle\DependencyInjection\Compiler\LdapConfigurationCompilerPass;

class OroLDAPBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
    }
}
