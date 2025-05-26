<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Request\Rest\EntityIdTransformer;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityIdTransformerTest extends TestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
    }

    private function getTransformer(bool $alwaysString = false): EntityIdTransformer
    {
        return new EntityIdTransformer($this->valueNormalizer, ['rest'], $alwaysString);
    }

    public function testTransformForEnumEntity(): void
    {
        $result = $this->getTransformer()->transform(
            'test_enum.option1',
            new EntityMetadata('Extend\Entity\EV_Test_Enum')
        );
        self::assertSame('option1', $result);
    }

    /**
     * @dataProvider transformProvider
     */
    public function testTransform(int|array $id, int|string $expectedResult): void
    {
        $result = $this->getTransformer()->transform($id, new EntityMetadata('Test\Entity'));
        self::assertSame($expectedResult, $result);
    }

    /**
     * @dataProvider transformProvider
     */
    public function testTransformWithAlwaysString(int|array $id, int|string $expectedResult): void
    {
        $result = $this->getTransformer(true)->transform($id, new EntityMetadata('Test\Entity'));
        self::assertSame((string)$expectedResult, $result);
    }

    public function transformProvider(): array
    {
        return [
            [123, 123],
            [['id1' => 123, 'id2' => 456], 'id1=123;id2=456'],
            [['id1' => 'key 1', 'id2' => 'key&1\'1'], 'id1=key+1;id2=key%261%271']
        ];
    }

    public function testReverseTransformForEnumEntity(): void
    {
        $result = $this->getTransformer()->reverseTransform(
            'option1',
            new EntityMetadata('Extend\Entity\EV_Test_Enum')
        );
        self::assertSame('test_enum.option1', $result);
    }

    public function testReverseTransformForEnumEntityWithHint(): void
    {
        $metadata = new EntityMetadata('Extend\Entity\EV_Test_Enum1');
        $metadata->setHints([['name' => 'HINT_ENUM_OPTION', 'value' => 'test_enum2']]);
        $result = $this->getTransformer()->reverseTransform('option1', $metadata);
        self::assertSame('test_enum2.option1', $result);
    }

    public function testReverseTransformForSingleIdentifier(): void
    {
        $entityClass = 'Test\Class';
        $value = '123';

        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'))->setDataType('integer');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);

        $result = $this->getTransformer()->reverseTransform($value, $metadata);
        self::assertSame(123, $result);
    }

    public function testReverseTransformForSingleIdentifierWhenFieldDataTypeIsString(): void
    {
        $entityClass = 'Test\Class';
        $value = 123;

        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'))->setDataType('string');

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $result = $this->getTransformer()->reverseTransform($value, $metadata);
        self::assertSame('123', $result);
    }

    public function testReverseTransformForCompositeIdentifier(): void
    {
        $entityClass = 'Test\Class';
        $value = 'id1=123;id2=456';

        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('integer');

        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->withConsecutive(['123', 'integer'], ['456', 'integer'])
            ->willReturnOnConsecutiveCalls(123, 456);

        $result = $this->getTransformer()->reverseTransform($value, $metadata);
        self::assertSame(
            ['id1' => 123, 'id2' => 456],
            $result
        );
    }

    public function testReverseTransformForCompositeIdentifierWhenFieldsDataTypeIsString(): void
    {
        $entityClass = 'Test\Class';
        $value = 'id1=123;id2=456';

        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('string');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('string');

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $result = $this->getTransformer()->reverseTransform($value, $metadata);
        self::assertSame(
            ['id1' => '123', 'id2' => '456'],
            $result
        );
    }

    public function testReverseTransformForCompositeIdentifierContainsEncodedChars(): void
    {
        $entityClass = 'Test\Class';
        $value = http_build_query(['id1' => 'key 1', 'id2' => 'key&1\'1'], '', ';');

        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('string');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('string');

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $result = $this->getTransformer()->reverseTransform($value, $metadata);
        self::assertSame(
            ['id1' => 'key 1', 'id2' => 'key&1\'1'],
            $result
        );
    }

    public function testReverseTransformForCompositeIdentifierWithInvalidField(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'The entity identifier contains the key "anotherId" which is not defined'
            . ' in composite identifier of the entity "Test\Class".'
        );

        $entityClass = 'Test\Class';
        $value = 'id1=123;anotherId=456';

        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('integer');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);

        $this->getTransformer()->reverseTransform($value, $metadata);
    }

    public function testReverseTransformForCompositeIdentifierWhenItDoesNotContainAllIdentifierFields(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'The entity identifier does not contain all keys defined in composite identifier of the entity'
            . ' "Test\Class".'
        );

        $entityClass = 'Test\Class';
        $value = 'id1=123';

        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('integer');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);

        $this->getTransformer()->reverseTransform($value, $metadata);
    }

    public function testReverseTransformForCompositeIdentifierThatDoesNotHaveFieldValue(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Unexpected identifier value "id1=123;id2" for composite identifier of the entity "Test\Class".'
        );

        $entityClass = 'Test\Class';
        $value = 'id1=123;id2';

        $metadata = new EntityMetadata($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('integer');

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);

        $this->getTransformer()->reverseTransform($value, $metadata);
    }
}
