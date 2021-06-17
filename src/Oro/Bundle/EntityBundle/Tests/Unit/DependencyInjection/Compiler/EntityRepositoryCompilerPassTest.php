<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityRepositoryCompilerPass;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\FirstEntity;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\FirstEntityRepository;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\SecondEntity;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\SecondEntityRepository;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\ThirdEntity;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

class EntityRepositoryCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityRepositoryCompilerPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->compiler = new EntityRepositoryCompilerPass();
    }

    public function testProcessWhenNoRepositoryFactoryAndOrmConfiguration()
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcessWhenNoRepositoryFactory()
    {
        $container = new ContainerBuilder();
        $container->register('doctrine.orm.configuration');

        $this->compiler->process($container);
    }

    public function testProcessWhenNoOrmConfiguration()
    {
        $container = new ContainerBuilder();
        $container->register('oro_entity.repository.factory');

        $this->compiler->process($container);
    }

    public function testProcessNoRepositoryServices()
    {
        $container = new ContainerBuilder();
        $repositoryFactoryDef = $container->register('oro_entity.repository.factory')
            ->setArguments([null, []]);
        $container->register('doctrine.orm.configuration');
        $firstConfigurationDef = $container
            ->setDefinition('doctrine.orm.first_configuration', new ChildDefinition('doctrine.orm.configuration'));
        $secondConfigurationDef = $container
            ->setDefinition('doctrine.orm.second_configuration', new ChildDefinition('doctrine.orm.configuration'));

        $this->compiler->process($container);

        $this->assertSame([], $repositoryFactoryDef->getArgument(1));
        $this->assertEquals(
            [['setRepositoryFactory', [new Reference('oro_entity.repository.factory')]]],
            $firstConfigurationDef->getMethodCalls()
        );
        $this->assertEquals(
            [['setRepositoryFactory', [new Reference('oro_entity.repository.factory')]]],
            $secondConfigurationDef->getMethodCalls()
        );
    }

    public function testProcessRepositoryServices()
    {
        $container = new ContainerBuilder();
        $repositoryFactoryDef = $container->register('oro_entity.repository.factory')
            ->setArguments([null, []]);
        $container->register('doctrine.orm.configuration');
        $firstConfigurationDef = $container
            ->setDefinition('doctrine.orm.first_configuration', new ChildDefinition('doctrine.orm.configuration'));
        $secondConfigurationDef = $container
            ->setDefinition('doctrine.orm.second_configuration', new ChildDefinition('doctrine.orm.configuration'));

        $container->register('oro_entity.abstract_repository')->setAbstract(true);

        $testRepositoryFirstDef = $container
            ->setDefinition('test.repository.first', new ChildDefinition('oro_entity.abstract_repository'))
            ->setClass('%first.entity.repository.class%')
            ->setArguments(['%first.entity.class%']);

        $testRepositorySecondDef = $container
            ->setDefinition('test.repository.second', new ChildDefinition('test.repository.second.abstract'));
        $container
            ->setDefinition('test.repository.second.abstract', new ChildDefinition('oro_entity.abstract_repository'))
            ->setAbstract(true)
            ->setClass(SecondEntityRepository::class)
            ->setArguments([SecondEntity::class]);

        $testRepositoryThirdDef = $container
            ->setDefinition('test.repository.third', new ChildDefinition('test.repository.third.abstract'))
            ->setArguments([ThirdEntity::class]);
        $container
            ->setDefinition('test.repository.third.abstract', new ChildDefinition('oro_entity.abstract_repository'))
            ->setAbstract(true);

        $container->setParameter('first.entity.repository.class', FirstEntityRepository::class);
        $container->setParameter('first.entity.class', FirstEntity::class);

        $this->compiler->process($container);

        $this->assertSame(
            [
                FirstEntity::class  => 'test.repository.first',
                SecondEntity::class => 'test.repository.second',
                ThirdEntity::class  => 'test.repository.third',
            ],
            $repositoryFactoryDef->getArgument(1)
        );
        $this->assertEquals(
            [['setRepositoryFactory', [new Reference('oro_entity.repository.factory')]]],
            $firstConfigurationDef->getMethodCalls()
        );
        $this->assertEquals(
            [['setRepositoryFactory', [new Reference('oro_entity.repository.factory')]]],
            $secondConfigurationDef->getMethodCalls()
        );

        $this->assertEquals(
            [FirstEntity::class, FirstEntityRepository::class],
            $testRepositoryFirstDef->getArguments()
        );
        $this->assertEquals(
            [SecondEntity::class, SecondEntityRepository::class],
            $testRepositorySecondDef->getArguments()
        );
        $this->assertEquals(
            [ThirdEntity::class],
            $testRepositoryThirdDef->getArguments()
        );
    }

    public function testProcessPrivateRepositoryService()
    {
        $container = new ContainerBuilder();
        $container->register('oro_entity.repository.factory')
            ->setArguments([null, []]);
        $container->register('doctrine.orm.configuration');
        $container
            ->setDefinition('doctrine.orm.first_configuration', new ChildDefinition('doctrine.orm.configuration'));
        $container
            ->setDefinition('doctrine.orm.second_configuration', new ChildDefinition('doctrine.orm.configuration'));

        $container->register('oro_entity.abstract_repository')->setAbstract(true);
        $container
            ->setDefinition('test.repository.first', new ChildDefinition('oro_entity.abstract_repository'))
            ->setPublic(false)
            ->setArguments([FirstEntity::class]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Repository service %s for class %s must be public', 'test.repository.first', FirstEntity::class)
        );

        $this->compiler->process($container);
    }

    public function testProcessRepositoryServiceWithoutArguments()
    {
        $container = new ContainerBuilder();
        $container->register('oro_entity.repository.factory')
            ->setArguments([null, []]);
        $container->register('doctrine.orm.configuration');
        $container
            ->setDefinition('doctrine.orm.first_configuration', new ChildDefinition('doctrine.orm.configuration'));
        $container
            ->setDefinition('doctrine.orm.second_configuration', new ChildDefinition('doctrine.orm.configuration'));

        $container->register('oro_entity.abstract_repository')->setAbstract(true);
        $container
            ->setDefinition('test.repository.first', new ChildDefinition('oro_entity.abstract_repository'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Repository service %s might accept only entity class and repository class as arguments',
                'test.repository.first'
            )
        );

        $this->compiler->process($container);
    }

    public function testProcessRepositoryServiceWithInvalidArguments()
    {
        $container = new ContainerBuilder();
        $container->register('oro_entity.repository.factory')
            ->setArguments([null, []]);
        $container->register('doctrine.orm.configuration');
        $container
            ->setDefinition('doctrine.orm.first_configuration', new ChildDefinition('doctrine.orm.configuration'));
        $container
            ->setDefinition('doctrine.orm.second_configuration', new ChildDefinition('doctrine.orm.configuration'));

        $container->register('oro_entity.abstract_repository')->setAbstract(true);
        $container
            ->setDefinition('test.repository.first', new ChildDefinition('oro_entity.abstract_repository'))
            ->setArguments([FirstEntity::class, FirstEntityRepository::class, 'EntityManagerDefinition']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Repository service %s might accept only entity class and repository class as arguments',
                'test.repository.first'
            )
        );

        $this->compiler->process($container);
    }

    public function testProcessNotExistingEntity()
    {
        $container = new ContainerBuilder();
        $container->register('oro_entity.repository.factory')
            ->setArguments([null, []]);
        $container->register('doctrine.orm.configuration');
        $container
            ->setDefinition('doctrine.orm.first_configuration', new ChildDefinition('doctrine.orm.configuration'));
        $container
            ->setDefinition('doctrine.orm.second_configuration', new ChildDefinition('doctrine.orm.configuration'));

        $container->register('oro_entity.abstract_repository')->setAbstract(true);
        $container
            ->setDefinition('test.repository.first', new ChildDefinition('oro_entity.abstract_repository'))
            ->setPublic(false)
            ->setArguments(['NotExistingEntity']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Entity class NotExistingEntity defined at repository service test.repository.first doesn\'t exist'
        );

        $this->compiler->process($container);
    }
}
