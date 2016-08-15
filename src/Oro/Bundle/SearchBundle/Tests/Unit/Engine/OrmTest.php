<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Engine\Orm;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Resolver\EntityTitleResolverInterface;

class OrmTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityTitleResolver;

    protected function setUp()
    {
        $config                = require rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'searchConfig.php';
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->registry        = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->mapper          = new ObjectMapper($this->eventDispatcher, $config);

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()->getMock();
        $mapperProvider = new SearchMappingProvider($eventDispatcher);
        $mapperProvider->setMappingConfig($config);
        $this->mapper->setMappingProvider($mapperProvider);
        $this->doctrineHelper  = new DoctrineHelper($this->registry);
        $this->entityTitleResolver = $this->getMock(EntityTitleResolverInterface::class);
    }

    protected function tearDown()
    {
        unset(
            $this->doctrineHelper,
            $this->mapper,
            $this->registry,
            $this->eventDispatcher,
            $this->entityTitleResolver
        );
    }

    public function testReindexAll()
    {
        $processedEntities = [];
        $engine            = $this->getEngineMock();

        $engine->expects($this->any())->method('reindexSingleEntity')
            ->willReturnCallback(
                function ($class) use (&$processedEntities) {
                    $processedEntities[] = $class;
                }
            );

        $engine->reindex();
        $this->assertSame(
            [
                'Oro\Bundle\DataBundle\Entity\Product',
                'Oro\Bundle\DataBundle\Entity\Customer',
                'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Customer',
                'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\ConcreteCustomer',
                'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\RepeatableTask',
                'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\ScheduledTask'
            ],
            $processedEntities
        );
    }

    /**
     * @dataProvider entityModeDataProvider
     *
     * @param string $entity
     * @param array  $expectedEntitiesToProcess
     */
    public function testReindexEntityWithMode($entity, array $expectedEntitiesToProcess)
    {
        $processedEntities = $clearedEntities = [];

        $engine = $this->getEngineMock();
        $engine->expects($this->never())->method('clearAllSearchIndexes');
        $engine->expects($this->any())->method('clearSearchIndexForEntity')
            ->willReturnCallback(
                function ($class) use (&$clearedEntities) {
                    $clearedEntities[] = $class;
                }
            );
        $engine->expects($this->any())->method('reindexSingleEntity')
            ->willReturnCallback(
                function ($class) use (&$processedEntities) {
                    $processedEntities[] = $class;
                }
            );

        $engine->reindex($entity);
        $this->assertSame($expectedEntitiesToProcess, $processedEntities);
        $this->assertSame($expectedEntitiesToProcess, $clearedEntities);
    }

    /**
     * @return array
     */
    public function entityModeDataProvider()
    {
        return [
            'with normal mode'                => [
                'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\ConcreteCustomer',
                ['Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\ConcreteCustomer']
            ],
            'with mode only descendants'      => [
                'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\AbstractTask',
                [
                    'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\RepeatableTask',
                    'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\ScheduledTask'
                ]
            ],
            'with mode including descendants' => [
                'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Customer',
                [
                    'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Customer',
                    'Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\ConcreteCustomer'
                ]
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Orm
     */
    protected function getEngineMock()
    {
        $arguments = [
            $this->registry,
            $this->eventDispatcher,
            $this->doctrineHelper,
            $this->mapper,
            $this->entityTitleResolver
        ];

        return $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Orm')
            ->setConstructorArgs($arguments)
            ->setMethods(['clearAllSearchIndexes', 'clearSearchIndexForEntity', 'reindexSingleEntity'])
            ->getMock();
    }
}
