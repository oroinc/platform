<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Transformer;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Transformer\MessageTransformer;

class MessageTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var MessageTransformer */
    private $transformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->transformer = new MessageTransformer($this->doctrineHelper);
    }

    public function testTransform()
    {
        $entity = new \stdClass();
        $entities = [$entity];

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->willReturn('stdClass');

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->willReturn(48);

        $this->assertEquals(
            [['class' => 'stdClass', 'entityIds' => [48 => 48]]],
            $this->transformer->transform($entities)
        );
    }

    public function testTransformFewDifferentEntities()
    {
        $entity1 = new \stdClass();
        $entity2 = new \stdClass();

        $entities = [$entity1, $entity2];

        $this->doctrineHelper->expects($this->at(0))
            ->method('getEntityClass')
            ->with($entity1)
            ->willReturn('stdClass1');

        $this->doctrineHelper->expects($this->at(1))
            ->method('getSingleEntityIdentifier')
            ->with($entity1)
            ->willReturn(48);

        $this->doctrineHelper->expects($this->at(2))
            ->method('getEntityClass')
            ->with($entity2)
            ->willReturn('stdClass2');

        $this->doctrineHelper->expects($this->at(3))
            ->method('getSingleEntityIdentifier')
            ->with($entity2)
            ->willReturn(54);

        $this->assertEquals(
            [
                ['class' => 'stdClass1','entityIds' => [48 => 48]],
                ['class' => 'stdClass2','entityIds' => [54 => 54]],
            ],
            $this->transformer->transform($entities)
        );
    }

    public function testTransformChunk()
    {
        $entitiesCount = MessageTransformer::CHUNK_SIZE * 3 + 10;
        $entities = array_fill(0, $entitiesCount, new \stdClass());

        $this->doctrineHelper->expects($this->exactly($entitiesCount))
            ->method('getEntityClass')
            ->willReturn('stdClass');

        $this->doctrineHelper->expects($this->exactly($entitiesCount))
            ->method('getSingleEntityIdentifier')
            ->will($this->returnCallback(function () {
                static $id = 0;
                return $id++;
            }));

        $messages = $this->transformer->transform($entities);
        $this->assertCount(4, $messages);
        $this->assertCount(MessageTransformer::CHUNK_SIZE, $messages[0]['entityIds']);
        $this->assertCount(10, $messages[3]['entityIds']);

        foreach ($messages as $message) {
            $this->assertNotEmpty($message);
        }
    }

    public function testTransformChunkStrictly()
    {
        $entitiesCount = MessageTransformer::CHUNK_SIZE;
        $entities = array_fill(0, $entitiesCount, new \stdClass());

        $this->doctrineHelper->expects($this->exactly($entitiesCount))
            ->method('getEntityClass')
            ->willReturn('stdClass');

        $this->doctrineHelper->expects($this->exactly($entitiesCount))
            ->method('getSingleEntityIdentifier')
            ->will($this->returnCallback(function () {
                static $id = 0;
                return $id++;
            }));

        $messages = $this->transformer->transform($entities);
        $this->assertCount(1, $messages);
        $this->assertCount(MessageTransformer::CHUNK_SIZE, $messages[0]['entityIds']);
    }

    public function testTransformEmpty()
    {
        $entities = [];

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityMetadata');

        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier');

        $this->assertEquals([], $this->transformer->transform($entities));
    }
}
