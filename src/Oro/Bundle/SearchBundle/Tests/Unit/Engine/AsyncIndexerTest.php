<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine;

use Oro\Bundle\SearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Entity\Item;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class AsyncIndexerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AsyncIndexer
     */
    private $indexer;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageProducer;

    /**
     * @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $baseIndexer;

    public function setUp()
    {
        $this->messageProducer = $this->getMock(MessageProducerInterface::class);

        $this->baseIndexer = $this->getMock(IndexerInterface::class);

        $this->indexer = new AsyncIndexer($this->baseIndexer, $this->messageProducer);
    }

    public function testSaveOne()
    {
        $entity = $this->getMock(Item::class);
        $entity->method('getId')
            ->willReturn(101);

        $context = ['test'];

        $expectedParams = [
            'entity' =>[
                'class' => get_class($entity),
                'id' => 101
            ],
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_SAVE, $expectedParams);

        $this->indexer->save($entity, $context);
    }

    public function testSaveMany()
    {
        $entity1 = $this->getMock(Item::class);
        $entity1->method('getId')
            ->willReturn(101);

        $entity2 = $this->getMock(Item::class);
        $entity2->method('getId')
            ->willReturn(102);

        $context = ['test'];

        $expectedParams = [
            'entity' =>[
                [
                    'class' => get_class($entity1),
                    'id' => 101
                ],
                [
                    'class' => get_class($entity2),
                    'id' => 102
                ]
            ],
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_SAVE, $expectedParams);

        $this->indexer->save([$entity1, $entity2], $context);
    }

    public function testDeleteOne()
    {
        $entity = $this->getMock(Item::class);
        $entity->method('getId')
            ->willReturn(101);

        $context = ['test'];

        $expectedParams = [
            'entity' =>[
                'class' => get_class($entity),
                'id' => 101
            ],
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_DELETE, $expectedParams);

        $this->indexer->delete($entity, $context);
    }

    public function testDeleteMany()
    {
        $entity1 = $this->getMock(Item::class);
        $entity1->method('getId')
            ->willReturn(101);

        $entity2 = $this->getMock(Item::class);
        $entity2->method('getId')
            ->willReturn(102);

        $context = ['test'];

        $expectedParams = [
            'entity' =>[
                [
                    'class' => get_class($entity1),
                    'id' => 101
                ],
                [
                    'class' => get_class($entity2),
                    'id' => 102
                ]
            ],
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_DELETE, $expectedParams);

        $this->indexer->delete([$entity1, $entity2], $context);
    }

    public function testGetClassesForReindex()
    {
        $class = '\StdClass';
        $context = ['foo', 'bar'];

        $this->baseIndexer->expects($this->once())
            ->method('getClassesForReindex')
            ->with($class, $context);

        $this->indexer->getClassesForReindex($class, $context);
    }

    public function testResetReindex()
    {
        $context = ['test'];

        $expectedParams = [
            'class' => Item::class,
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_RESET_INDEX, $expectedParams);

        $this->indexer->resetIndex(Item::class, $context);
    }

    public function testReindex()
    {
        $context = ['test'];

        $expectedParams = [
            'class' => Item::class,
            'context' => [
                'test'
            ]
        ];

        $this->messageProducer->expects($this->atLeastOnce())
            ->method('send')
            ->with(AsyncIndexer::TOPIC_REINDEX, $expectedParams);

        $this->indexer->reindex(Item::class, $context);
    }
}
