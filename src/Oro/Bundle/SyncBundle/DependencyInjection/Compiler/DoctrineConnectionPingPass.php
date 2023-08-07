<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Adds doctrine connection name for periodical ping
 */
class DoctrineConnectionPingPass implements CompilerPassInterface
{
    private const DEFINITION_NAME = 'oro_sync.periodic.db_ping';

    private string $doctrineConnectionName;

    public function __construct(string $doctrineConnectionName)
    {
        $this->doctrineConnectionName = $doctrineConnectionName;
    }

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::DEFINITION_NAME)) {
            $container
                ->getDefinition(self::DEFINITION_NAME)
                ->addMethodCall(
                    'addDoctrineConnectionName',
                    [$this->doctrineConnectionName]
                );
        }
    }
}
