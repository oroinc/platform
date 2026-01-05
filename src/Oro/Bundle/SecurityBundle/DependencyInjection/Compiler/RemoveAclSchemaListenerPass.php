<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The "security.acl.dbal.schema_listener" is not needed in the Platform because
 * ACL tables are created by a migration.
 * @see \Oro\Bundle\SecurityBundle\Migrations\Schema\OroSecurityBundleInstaller::up
 */
class RemoveAclSchemaListenerPass implements CompilerPassInterface
{
    public const ACL_SCHEMA_LISTENER_SERVICE_ID = 'security.acl.dbal.schema_listener';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if ($container->has(self::ACL_SCHEMA_LISTENER_SERVICE_ID)) {
            $container->removeDefinition(self::ACL_SCHEMA_LISTENER_SERVICE_ID);
        }
    }
}
