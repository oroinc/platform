<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\EntityBundle\Form\DataTransformer\EntityReferenceToStringTransformer;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tests\Unit\Form\Stub\TestEntity;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntityReferenceToStringTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityReferenceToStringTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function ($entity) {
                if ($entity instanceof TestEntity) {
                    return 1;
                }

                return null;
            });
        $doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($entityClass) {
                return new $entityClass();
            });

        $this->transformer = new EntityReferenceToStringTransformer($doctrineHelper);
    }

    /**
     * @dataProvider transformProvider
     */
    public function testTransform(?object $value, ?string $expectedValue): void
    {
        $this->assertEquals($expectedValue, $this->transformer->transform($value));
    }

    public function transformProvider(): array
    {
        return [
            [
                null,
                null,
            ],
            [
                new TestEntity(),
                json_encode(['entityClass' => TestEntity::class, 'entityId' => 1], JSON_THROW_ON_ERROR),
            ],
        ];
    }

    public function testTransformWhenInvalidValueType(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "object", "int" given');

        $this->transformer->transform(123);
    }

    /**
     * @dataProvider reverseTransformProvider
     */
    public function testReverseTransform(?string $value, ?object $expectedValue): void
    {
        $this->assertEquals($expectedValue, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformProvider(): array
    {
        return [
            [
                null,
                null,
            ],
            [
                json_encode(['entityClass' => TestEntity::class, 'entityId' => 1], JSON_THROW_ON_ERROR),
                new TestEntity(),
            ],
        ];
    }

    public function testReverseTransformWithInvalidValueType(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a string.');

        $this->transformer->reverseTransform(123);
    }

    public function testReverseTransformWithMissingEntityClass(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array with "entityClass" element after decoding a string.');

        $this->transformer->reverseTransform(
            json_encode(['entityId' => 1], JSON_THROW_ON_ERROR)
        );
    }

    public function testReverseTransformWithMissingEntityId(): void
    {
        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected an array with "entityId" element after decoding a string.');

        $this->transformer->reverseTransform(
            json_encode(['entityClass' => TestEntity::class], JSON_THROW_ON_ERROR)
        );
    }
}
