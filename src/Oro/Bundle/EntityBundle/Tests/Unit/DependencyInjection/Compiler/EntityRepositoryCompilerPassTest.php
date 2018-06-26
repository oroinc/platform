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
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

class EntityRepositoryCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityRepositoryCompilerPass */
    protected $compilerPass;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->compilerPass = new EntityRepositoryCompilerPass();
    }

    /**
     * @param array $services
     *
     * @dataProvider processWithMissingServicesDataProvider
     */
    public function testProcessWithMissingServices(array $services)
    {
        $container = $this->prepareContainer($services);
        $container->expects($this->never())
            ->method('getDefinitions');
        $this->compilerPass->process($container);
    }

    /**
     * @return array
     */
    public function processWithMissingServicesDataProvider()
    {
        return [
            'no required services' => ['services' => []],
            'only factory'         => ['services' => ['oro_entity.repository.factory' => []]],
            'only configuration'   => ['services' => ['doctrine.orm.configuration' => []]],
        ];
    }

    public function testProcessNoRepositoryServices()
    {
        $container = $this->prepareContainer(
            [
                'oro_entity.repository.factory' => ['arguments' => [null, []]],
                'doctrine.orm.configuration' => [],
                'doctrine.orm.first_configuration' => ['parent' => 'doctrine.orm.configuration'],
                'doctrine.orm.second_configuration' => ['parent' => 'doctrine.orm.configuration']
            ]
        );
        $this->compilerPass->process($container);

        $repositoryFactory = $container->getDefinition('oro_entity.repository.factory');
        $firstConfiguration = $container->getDefinition('doctrine.orm.first_configuration');
        $secondConfiguration = $container->getDefinition('doctrine.orm.second_configuration');

        $this->assertSame([], $repositoryFactory->getArgument(1));
        $this->assertEquals(
            [['setRepositoryFactory', [new Reference('oro_entity.repository.factory')]]],
            $firstConfiguration->getMethodCalls()
        );
        $this->assertEquals(
            [['setRepositoryFactory', [new Reference('oro_entity.repository.factory')]]],
            $secondConfiguration->getMethodCalls()
        );
    }

    public function testProcessRepositoryServices()
    {
        $container = $this->prepareContainer(
            [
                'oro_entity.abstract_repository' => ['abstract' => true],
                'oro_entity.repository.factory' => ['arguments' => [null, []]],
                'doctrine.orm.configuration' => [],
                'doctrine.orm.first_configuration' => ['parent' => 'doctrine.orm.configuration'],
                'doctrine.orm.second_configuration' => ['parent' => 'doctrine.orm.configuration'],
                'test.repository.first' => [
                    'parent' => 'oro_entity.abstract_repository',
                    'class' => '%first.entity.repository.class%',
                    'arguments' => ['%first.entity.class%']
                ],
                'test.repository.second.abstract' => [
                    'parent' => 'oro_entity.abstract_repository',
                    'abstract' => true,
                    'class' => SecondEntityRepository::class,
                    'arguments' => [SecondEntity::class],
                ],
                'test.repository.second' => [
                    'parent' => 'test.repository.second.abstract',
                ],
                'test.repository.third.abstract' => [
                    'parent' => 'oro_entity.abstract_repository',
                    'abstract' => true,
                ],
                'test.repository.third' => [
                    'parent' => 'test.repository.third.abstract',
                    'arguments' => [ThirdEntity::class],
                ],
            ],
            [
                'first.entity.repository.class' => FirstEntityRepository::class,
                'first.entity.class' => FirstEntity::class,
            ]
        );
        $this->compilerPass->process($container);

        $repositoryFactory = $container->getDefinition('oro_entity.repository.factory');
        $firstConfiguration = $container->getDefinition('doctrine.orm.first_configuration');
        $secondConfiguration = $container->getDefinition('doctrine.orm.second_configuration');

        $this->assertSame(
            [
                FirstEntity::class => 'test.repository.first',
                SecondEntity::class => 'test.repository.second',
                ThirdEntity::class => 'test.repository.third',
            ],
            $repositoryFactory->getArgument(1)
        );
        $this->assertEquals(
            [['setRepositoryFactory', [new Reference('oro_entity.repository.factory')]]],
            $firstConfiguration->getMethodCalls()
        );
        $this->assertEquals(
            [['setRepositoryFactory', [new Reference('oro_entity.repository.factory')]]],
            $secondConfiguration->getMethodCalls()
        );

        $this->assertEquals(
            [FirstEntity::class, FirstEntityRepository::class],
            $container->getDefinition('test.repository.first')->getArguments()
        );
        $this->assertEquals(
            [SecondEntity::class, SecondEntityRepository::class],
            $container->getDefinition('test.repository.second')->getArguments()
        );
        $this->assertEquals(
            [ThirdEntity::class],
            $container->getDefinition('test.repository.third')->getArguments()
        );
    }

    public function testProcessPrivateRepositoryService()
    {
        $container = $this->prepareContainer(
            [
                'oro_entity.abstract_repository' => ['abstract' => true],
                'oro_entity.repository.factory' => ['arguments' => [null, []]],
                'doctrine.orm.configuration' => [],
                'test.repository.first' => [
                    'public' => false,
                    'parent' => 'oro_entity.abstract_repository',
                    'arguments' => [FirstEntity::class]
                ],
            ]
        );
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Repository service %s for class %s must be public', 'test.repository.first', FirstEntity::class)
        );

        $this->compilerPass->process($container);
    }

    public function testProcessRepositoryServiceWithoutArguments()
    {
        $container = $this->prepareContainer(
            [
                'oro_entity.abstract_repository' => ['abstract' => true],
                'oro_entity.repository.factory' => ['arguments' => [null, []]],
                'doctrine.orm.configuration' => [],
                'test.repository.first' => [
                    'parent' => 'oro_entity.abstract_repository',
                ],
            ]
        );
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Repository service %s might accept only entity class and repository class as arguments',
                'test.repository.first'
            )
        );

        $this->compilerPass->process($container);
    }

    public function testProcessRepositoryServiceWithInvalidArguments()
    {
        $container = $this->prepareContainer(
            [
                'oro_entity.abstract_repository' => ['abstract' => true],
                'oro_entity.repository.factory' => ['arguments' => [null, []]],
                'doctrine.orm.configuration' => [],
                'test.repository.first' => [
                    'parent' => 'oro_entity.abstract_repository',
                    'arguments' => [FirstEntity::class, FirstEntityRepository::class, 'EntityManagerDefinition']
                ],
            ]
        );
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Repository service %s might accept only entity class and repository class as arguments',
                'test.repository.first'
            )
        );

        $this->compilerPass->process($container);
    }

    public function testProcessNotExistingEntity()
    {
        $container = $this->prepareContainer(
            [
                'oro_entity.abstract_repository' => ['abstract' => true],
                'oro_entity.repository.factory' => ['arguments' => [null, []]],
                'doctrine.orm.configuration' => [],
                'test.repository.first' => [
                    'public' => false,
                    'parent' => 'oro_entity.abstract_repository',
                    'arguments' => ['NotExistingEntity']
                ],
            ]
        );
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf('Entity class NotExistingEntity defined at repository service test.repository.first doesn\'t exist')
        );

        $this->compilerPass->process($container);
    }

    /**
     * [
     *     '<id>' => [
     *          'class' => '<class>',
     *          'arguments' => [...],
     *          'parent' => '<parentId>',
     *          'abstract' => true|false,
     *          'public' => true|false,
     *      ],
     *      ...
     * ]
     *
     * @param array $services
     * @return ContainerBuilder|\PHPUnit\Framework\MockObject\MockObject
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function prepareContainer(array $services = [], array $parameters = [])
    {
        $definitions = [];
        foreach ($services as $id => $config) {
            $class = array_key_exists('class', $config) ? $config['class'] : null;
            $arguments = array_key_exists('arguments', $config) ? $config['arguments'] : [];

            if (array_key_exists('parent', $config)) {
                $definition = new ChildDefinition($config['parent']);
                if ($class) {
                    $definition->setClass($class);
                }
                if ($arguments) {
                    $definition->setArguments($arguments);
                }
            } else {
                $definition = new Definition($class, $arguments);
            }

            if (array_key_exists('abstract', $config)) {
                $definition->setAbstract($config['abstract']);
            }

            if (array_key_exists('public', $config)) {
                $definition->setPublic($config['public']);
            }

            $definitions[$id] = $definition;
        }

        $container = $this->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->expects($this->any())
            ->method('getDefinitions')
            ->willReturn($definitions);
        $container->expects($this->any())
            ->method('hasDefinition')
            ->willReturnCallback(
                function ($id) use ($definitions) {
                    return !empty($definitions[$id]);
                }
            );
        $container->expects($this->any())
            ->method('getDefinition')
            ->willReturnCallback(
                function ($id) use ($definitions) {
                    return $definitions[$id];
                }
            );
        $container->expects($this->any())
            ->method('hasParameter')
            ->willReturnCallback(
                function ($id) use ($parameters) {
                    return !empty($parameters[$id]);
                }
            );
        $container->expects($this->any())
            ->method('getParameter')
            ->willReturnCallback(
                function ($id) use ($parameters) {
                    return $parameters[$id];
                }
            );

        return $container;
    }
}
