<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\OrganizationBundle\DependencyInjection\Compiler\OwnerDeletionManagerPass;
use Oro\Bundle\OrganizationBundle\Ownership\OwnerDeletionManager;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class OwnerDeletionManagerPassTest extends \PHPUnit\Framework\TestCase
{
    private OwnerDeletionManagerPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new OwnerDeletionManagerPass();
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $managerRegistryDef = $container->register(
            'oro_organization.owner_deletion_manager',
            OwnerDeletionManager::class
        );

        $container->register('checker1')
            ->addTag('oro_organization.owner_assignment_checker', ['entity' => 'default']);
        $container->register('checker2')
            ->addTag('oro_organization.owner_assignment_checker', ['entity' => 'Test\Entity1']);

        $this->compiler->process($container);

        $managerServiceLocatorRef = $managerRegistryDef->getArgument('$checkerContainer');
        self::assertInstanceOf(Reference::class, $managerServiceLocatorRef);
        $managerServiceLocatorDef = $container->getDefinition((string)$managerServiceLocatorRef);
        self::assertEquals(ServiceLocator::class, $managerServiceLocatorDef->getClass());
        self::assertEquals(
            [
                'default'      => new ServiceClosureArgument(new Reference('checker1')),
                'Test\Entity1' => new ServiceClosureArgument(new Reference('checker2'))
            ],
            $managerServiceLocatorDef->getArgument(0)
        );
    }

    public function testProcessWhenCheckerDoesNotHaveEntityTagAttribute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "entity" is required for "oro_organization.owner_assignment_checker" tag.'
            . ' Service: "checker2".'
        );

        $container = new ContainerBuilder();
        $container->register(
            'oro_organization.owner_deletion_manager',
            OwnerDeletionManager::class
        );

        $container->register('checker1')
            ->addTag('oro_organization.owner_assignment_checker', ['entity' => 'default']);
        $container->register('checker2')
            ->addTag('oro_organization.owner_assignment_checker');

        $this->compiler->process($container);
    }

    public function testProcessWhenCheckerIsDuplicated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The service "checker2" must not have the tag "oro_organization.owner_assignment_checker"'
            . ' and the entity "Test\Entity1" because there is another service ("checker1") with this tag and entity.'
            . ' Use a decoration of "checker1" service to extend it or create a compiler pass'
            . ' for the dependency injection container to override "checker1" service completely.'
        );

        $container = new ContainerBuilder();
        $container->register(
            'oro_organization.owner_deletion_manager',
            OwnerDeletionManager::class
        );

        $container->register('checker1')
            ->addTag('oro_organization.owner_assignment_checker', ['entity' => 'Test\Entity1']);
        $container->register('checker2')
            ->addTag('oro_organization.owner_assignment_checker', ['entity' => 'Test\Entity1']);

        $this->compiler->process($container);
    }
}
