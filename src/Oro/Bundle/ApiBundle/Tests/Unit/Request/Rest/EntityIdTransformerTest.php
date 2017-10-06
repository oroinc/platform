<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Request\Rest\EntityIdTransformer;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

class EntityIdTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformer */
    protected $entityIdTransformer;

    protected function setUp()
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->entityIdTransformer = new EntityIdTransformer($this->valueNormalizer);
    }

    /**
     * @dataProvider transformProvider
     */
    public function testTransform($id, $expectedResult)
    {
        $result = $this->entityIdTransformer->transform($id, new EntityMetadata());
        $this->assertSame($expectedResult, $result);
    }

    public function transformProvider()
    {
        return [
            [123, '123'],
            ['key 1', 'key+1'],
            ['key&1\'1', 'key%261%271'],
            [['id1' => 123, 'id2' => 456], 'id1=123;id2=456'],
            [['id1' => 'key 1', 'id2' => 'key&1\'1'], 'id1=key+1;id2=key%261%271'],
        ];
    }

    public function testReverseTransformForSingleIdentifier()
    {
        $entityClass = 'Test\Class';
        $value = '123';

        $metadata = new EntityMetadata();
        $metadata->setClassName($entityClass);
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'))->setDataType('integer');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);

        $result = $this->entityIdTransformer->reverseTransform($value, $metadata);
        $this->assertSame(123, $result);
    }

    public function testReverseTransformForSingleIdentifierContainsEncodedChars()
    {
        $entityClass = 'Test\Class';
        $value = urlencode('key 1&1\'1');

        $metadata = new EntityMetadata();
        $metadata->setClassName($entityClass);
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'))->setDataType('string');

        $result = $this->entityIdTransformer->reverseTransform($value, $metadata);
        $this->assertSame('key 1&1\'1', $result);
    }

    public function testReverseTransformForSingleIdentifierContainsEncodedCharsThatAlreadyDecoded()
    {
        $entityClass = 'Test\Class';
        $value = 'key 1&1\'1';

        $metadata = new EntityMetadata();
        $metadata->setClassName($entityClass);
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'))->setDataType('string');

        $result = $this->entityIdTransformer->reverseTransform($value, $metadata);
        $this->assertSame('key 1&1\'1', $result);
    }

    public function testReverseTransformForSingleIdentifierWhenFieldDataTypeIsString()
    {
        $entityClass = 'Test\Class';
        $value = '123';

        $metadata = new EntityMetadata();
        $metadata->setClassName($entityClass);
        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'))->setDataType('string');

        $this->valueNormalizer->expects($this->never())
            ->method('normalizeValue');

        $result = $this->entityIdTransformer->reverseTransform($value, $metadata);
        $this->assertSame($value, $result);
    }

    public function testReverseTransformForCompositeIdentifier()
    {
        $entityClass = 'Test\Class';
        $value = 'id1=123;id2=456';

        $metadata = new EntityMetadata();
        $metadata->setClassName($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('integer');

        $this->valueNormalizer->expects($this->at(0))
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);
        $this->valueNormalizer->expects($this->at(1))
            ->method('normalizeValue')
            ->with('456', 'integer')
            ->willReturn(456);

        $result = $this->entityIdTransformer->reverseTransform($value, $metadata);
        $this->assertSame(
            ['id1' => 123, 'id2' => 456],
            $result
        );
    }

    public function testReverseTransformForCompositeIdentifierWhenFieldsDataTypeIsString()
    {
        $entityClass = 'Test\Class';
        $value = 'id1=123;id2=456';

        $metadata = new EntityMetadata();
        $metadata->setClassName($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('string');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('string');

        $this->valueNormalizer->expects($this->never())
            ->method('normalizeValue');

        $result = $this->entityIdTransformer->reverseTransform($value, $metadata);
        $this->assertSame(
            ['id1' => '123', 'id2' => '456'],
            $result
        );
    }

    public function testReverseTransformForCompositeIdentifierContainsEncodedChars()
    {
        $entityClass = 'Test\Class';
        $value = http_build_query(['id1' => 'key 1', 'id2' => 'key&1\'1'], '', ';');

        $metadata = new EntityMetadata();
        $metadata->setClassName($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('string');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('string');

        $this->valueNormalizer->expects($this->never())
            ->method('normalizeValue');

        $result = $this->entityIdTransformer->reverseTransform($value, $metadata);
        $this->assertSame(
            ['id1' => 'key 1', 'id2' => 'key&1\'1'],
            $result
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage The entity identifier contains the key "anotherId" which is not defined in composite identifier of the entity "Test\Class".
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformForCompositeIdentifierWithInvalidField()
    {
        $entityClass = 'Test\Class';
        $value = 'id1=123;anotherId=456';

        $metadata = new EntityMetadata();
        $metadata->setClassName($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('integer');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);

        $this->entityIdTransformer->reverseTransform($value, $metadata);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage The entity identifier does not contain all keys defined in composite identifier of the entity "Test\Class".
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformForCompositeIdentifierWhenItDoesNotContainAllIdentifierFields()
    {
        $entityClass = 'Test\Class';
        $value = 'id1=123';

        $metadata = new EntityMetadata();
        $metadata->setClassName($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('integer');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);

        $this->entityIdTransformer->reverseTransform($value, $metadata);
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Unexpected identifier value "id1=123;id2" for composite identifier of the entity "Test\Class".
     */
    // @codingStandardsIgnoreEnd
    public function testReverseTransformForCompositeIdentifierThatDoesNotHaveFieldValue()
    {
        $entityClass = 'Test\Class';
        $value = 'id1=123;id2';

        $metadata = new EntityMetadata();
        $metadata->setClassName($entityClass);
        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('integer');

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with('123', 'integer')
            ->willReturn(123);

        $this->entityIdTransformer->reverseTransform($value, $metadata);
    }
}
