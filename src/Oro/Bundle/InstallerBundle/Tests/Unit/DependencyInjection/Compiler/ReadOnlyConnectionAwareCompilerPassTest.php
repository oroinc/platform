<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\InstallerBundle\DependencyInjection\Compiler\ReadOnlyConnectionAwareCompilerPass;
use Oro\Bundle\InstallerBundle\Provider\ReadOnlyConnectionAwareInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ReadOnlyConnectionAwareCompilerPassTest extends TestCase
{
    public function testProcessAddsSetterCallForAwareServices(): void
    {
        $awareClass = get_class($this->createMock(ReadOnlyConnectionAwareInterface::class));

        $container = new ContainerBuilder();
        $container->setParameter('oro_installer.database.readonly', ['readonly']);
        $container->setDefinition(
            'aware_service',
            new Definition($awareClass)
        );
        $container->setDefinition(
            'regular_service',
            new Definition(\stdClass::class)
        );

        $compilerPass = new ReadOnlyConnectionAwareCompilerPass();
        $compilerPass->process($container);

        self::assertSame(
            [['setReadOnlyConnections', [['readonly']]]],
            $container->getDefinition('aware_service')->getMethodCalls()
        );
        self::assertSame([], $container->getDefinition('regular_service')->getMethodCalls());
    }

    public function testProcessSkipsWhenParameterDoesNotExist(): void
    {
        $awareClass = get_class($this->createMock(ReadOnlyConnectionAwareInterface::class));

        $container = new ContainerBuilder();
        $container->setDefinition(
            'aware_service',
            new Definition($awareClass)
        );

        $compilerPass = new ReadOnlyConnectionAwareCompilerPass();
        $compilerPass->process($container);

        self::assertSame([], $container->getDefinition('aware_service')->getMethodCalls());
    }

    public function testProcessSkipsServiceWhenClassAutoloadFails(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('oro_installer.database.readonly', ['readonly']);
        $container->setDefinition(
            'autoload_failing_service',
            new Definition('Liip\\ImagineBundle\\Templating\\Helper\\FilterHelper')
        );

        $compilerPass = new ReadOnlyConnectionAwareCompilerPass();
        $compilerPass->process($container);

        self::assertSame([], $container->getDefinition('autoload_failing_service')->getMethodCalls());
    }
}
