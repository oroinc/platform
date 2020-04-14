<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\EntityBundle\Form\DataTransformer\EntityReferenceToStringTransformer;
use Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub\TestEntity;

class EntityReferenceToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityReferenceToStringTransformer */
    protected $transformer;

    protected function setUp(): void
    {
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->will($this->returnCallback(function ($entity) {
                if ($entity instanceof TestEntity) {
                    return 1;
                }

                return null;
            }));
        $doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->will($this->returnCallback(function ($entityClass) {
                return new $entityClass();
            }));

        $this->transformer = new EntityReferenceToStringTransformer($doctrineHelper);
    }

    /**
     * @dataProvider transformProvider
     */
    public function testTransform($value, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->transformer->transform($value));
    }

    public function transformProvider()
    {
        return [
            [
                null,
                null,
            ],
            [
                new TestEntity(),
                json_encode([
                    'entityClass' => 'Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub\TestEntity',
                    'entityId'    => 1,
                ]),
            ],
        ];
    }

    public function testTransformWhenInvalidValueType()
    {
        $this->expectException(\Symfony\Component\Form\Exception\UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "object", "integer" given');

        $this->transformer->transform(123);
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform($value, $expectedValue)
    {
        $this->assertEquals($expectedValue, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformProvider()
    {
        return [
            [
                null,
                null,
            ],
            [
                json_encode([
                    'entityClass' => 'Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub\TestEntity',
                    'entityId'    => 1,
                ]),
                new TestEntity(),
            ],
        ];
    }

    public function testReverseTransformWithInvalidValueType()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a string.');

        $this->transformer->reverseTransform(123);
    }

    public function testReverseTransformWithMissingEntityClass()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array with "entityClass" element after decoding a string.');

        $this->transformer->reverseTransform(
            json_encode([
                'entityId' => 1
            ])
        );
    }

    public function testReverseTransformWithMissingEntityId()
    {
        $this->expectException(\Symfony\Component\Form\Exception\TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array with "entityId" element after decoding a string.');

        $this->transformer->reverseTransform(
            json_encode([
                'entityClass' => 'Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub\TestEntity',
            ])
        );
    }
}
