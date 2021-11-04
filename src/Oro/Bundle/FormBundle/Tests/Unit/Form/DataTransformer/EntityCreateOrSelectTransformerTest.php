<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreateOrSelectTransformer;
use Oro\Bundle\FormBundle\Form\Type\OroEntityCreateOrSelectType;
use Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

class EntityCreateOrSelectTransformerTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = 'TestClass';
    private const DEFAULT_MODE = 'default';

    /** @var EntityCreateOrSelectTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturnCallback(function (TestEntity $entity) {
                return $entity->getId();
            });

        $this->transformer = new EntityCreateOrSelectTransformer(
            $doctrineHelper,
            self::CLASS_NAME,
            self::DEFAULT_MODE
        );
    }

    /**
     * @dataProvider transformDataProvider
     */
    public function testTransform(?object $value, array $expected)
    {
        $this->assertEquals($expected, $this->transformer->transform($value));
    }

    public function transformDataProvider(): array
    {
        return [
            'no value' => [
                'value' => null,
                'expected' => [
                    'new_entity' => null,
                    'existing_entity' => null,
                    'mode' => self::DEFAULT_MODE
                ]
            ],
            'new entity' => [
                'value' => new TestEntity(),
                'expected' => [
                    'new_entity' => new TestEntity(),
                    'existing_entity' => null,
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                ]
            ],
            'existing entity' => [
                'value' => new TestEntity(1),
                'expected' => [
                    'new_entity' => null,
                    'existing_entity' => new TestEntity(1),
                    'mode' => OroEntityCreateOrSelectType::MODE_VIEW,
                ]
            ],
        ];
    }

    /**
     * @dataProvider transformExceptionDataProvider
     */
    public function testTransformException(string $value, string $exception, string $message)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->transformer->transform($value);
    }

    public function transformExceptionDataProvider(): array
    {
        return [
            'invalid type' => [
                'value' => 'not an object',
                'exception' => UnexpectedTypeException::class,
                'message' => 'Expected argument of type "object", "string" given',
            ]
        ];
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(?array $value, ?object $expected)
    {
        $this->assertEquals($expected, $this->transformer->reverseTransform($value));
    }

    public function reverseTransformDataProvider(): array
    {
        return [
            'null' => [
                'value' => null,
                'expected' => null,
            ],
            'create mode' => [
                'value' => [
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                    'new_entity' => new TestEntity(),
                ],
                'expected' => new TestEntity(),
            ],
            'view mode' => [
                'value' => [
                    'mode' => OroEntityCreateOrSelectType::MODE_VIEW,
                    'existing_entity' => new TestEntity(1),
                ],
                'expected' => new TestEntity(1),
            ],
            'grid mode' => [
                'value' => [
                    'mode' => OroEntityCreateOrSelectType::MODE_GRID,
                ],
                'expected' => null,
            ],
        ];
    }

    /**
     * @dataProvider reverseTransformExceptionDataProvider
     */
    public function testReverseTransformException(object|array $value, string $exception, string $message)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $this->transformer->reverseTransform($value);
    }

    public function reverseTransformExceptionDataProvider(): array
    {
        return [
            'invalid type' => [
                'value' => new TestEntity('not an array'),
                'exception' => UnexpectedTypeException::class,
                'message' => 'Expected argument of type "array",'
                    . ' "Oro\Bundle\FormBundle\Tests\Unit\Form\Stub\TestEntity" given',
            ],
            'empty mode' => [
                'value' => [],
                'exception' => TransformationFailedException::class,
                'message' => 'Data parameter "mode" is required',
            ],
            'no new entity' => [
                'value' => [
                    'mode' => OroEntityCreateOrSelectType::MODE_CREATE,
                ],
                'exception' => TransformationFailedException::class,
                'message' => 'Data parameter "new_entity" is required',
            ],
            'no existing entity' => [
                'value' => [
                    'mode' => OroEntityCreateOrSelectType::MODE_VIEW,
                ],
                'exception' => TransformationFailedException::class,
                'message' => 'Data parameter "existing_entity" is required',
            ],
        ];
    }
}
