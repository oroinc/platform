<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\FirstEntity;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\FirstEntityRepository;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\SecondEntity;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\ThirdEntity;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\EntityRepositoryCompilerPass;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\SecondEntityRepository;

class EntityRepositoryCompilerPassTest extends \PHPUnit_Framework_TestCase
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

    public function testProcessWithMissingServices()
    {
        $container = $this->prepareContainer();
        $container->expects($this->never())
            ->method('getDefinitions');
        $this->compilerPass->process($container);

        $container = $this->prepareContainer(['oro_entity.repository.factory' => []]);
        $container->expects($this->never())
            ->method('getDefinitions');
        $this->compilerPass->process($container);

        $container = $this->prepareContainer(['doctrine.orm.configuration' => []]);
        $container->expects($this->never())
            ->method('getDefinitions');
        $this->compilerPass->process($container);
    }

    public function testProcessNoRepositoryServices()
    {
        $container = $this->prepareContainer(
            [
                'oro_entity.repository.factory' => ['arguments' => [null, []]],
                'doctrine.orm.configuration' => []
            ]
        );
        $this->compilerPass->process($container);

        $repositoryFactory = $container->getDefinition('oro_entity.repository.factory');
        $doctrineConfiguration = $container->getDefinition('doctrine.orm.configuration');

        $this->assertSame([], $repositoryFactory->getArgument(1));
        $this->assertEquals(
            [['setRepositoryFactory', [$repositoryFactory]]],
            $doctrineConfiguration->getMethodCalls()
        );
    }

    public function testProcessRepositoryServices()
    {
        $container = $this->prepareContainer(
            [
                'oro_entity.abstract_repository' => ['abstract' => true],
                'oro_entity.repository.factory' => ['arguments' => [null, []]],
                'doctrine.orm.configuration' => [],
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
        $doctrineConfiguration = $container->getDefinition('doctrine.orm.configuration');

        $this->assertSame(
            [
                FirstEntity::class => 'test.repository.first',
                SecondEntity::class => 'test.repository.second',
                ThirdEntity::class => 'test.repository.third',
            ],
            $repositoryFactory->getArgument(1)
        );
        $this->assertEquals(
            [['setRepositoryFactory', [$repositoryFactory]]],
            $doctrineConfiguration->getMethodCalls()
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

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage Repository service test.repository.first for class Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Stub\FirstEntity must be public
     */
    // @codingStandardsIgnoreEnd
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
        $this->compilerPass->process($container);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage Repository service test.repository.first might accept only entity class and repository class as arguments
     */
    // @codingStandardsIgnoreEnd
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
        $this->compilerPass->process($container);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage Repository service test.repository.first might accept only entity class and repository class as arguments
     */
    // @codingStandardsIgnoreEnd
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
        $this->compilerPass->process($container);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
     * @expectedExceptionMessage Entity class NotExistingEntity defined at repository service test.repository.first doesn't exist
     */
    // @codingStandardsIgnoreEnd
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
     * @return ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject
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
                $definition = new DefinitionDecorator($config['parent']);
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
