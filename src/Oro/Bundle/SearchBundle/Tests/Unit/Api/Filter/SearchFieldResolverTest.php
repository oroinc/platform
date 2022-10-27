<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Api\Filter;

use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\SearchBundle\Api\Filter\SearchFieldResolver;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchFieldResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchFieldResolver */
    private $fieldResolver;

    protected function setUp(): void
    {
        $this->fieldResolver = new SearchFieldResolver(
            [
                'field_1'    => ['type' => 'integer'],
                'field2'     => ['type' => 'decimal'],
                'field3'     => ['type' => null],
                'field4'     => [],
                'price_UNIT' => ['type' => 'decimal']
            ],
            [
                'field1'               => 'field_1',
                'another'              => 'another_field',
                'price_(?<UNIT>\w+)'   => 'price_{UNIT}',
                'another_(?<UNIT>\w+)' => 'another_{UNIT}'
            ]
        );
    }

    public function testResolveFieldNameForFieldWithMapping()
    {
        self::assertEquals(
            'field_1',
            $this->fieldResolver->resolveFieldName('field1')
        );
    }

    public function testResolveFieldTypeForFieldWithMapping()
    {
        self::assertEquals(
            'integer',
            $this->fieldResolver->resolveFieldType('field1')
        );
    }

    public function testResolveFieldNameForFieldWithoutMapping()
    {
        self::assertEquals(
            'field2',
            $this->fieldResolver->resolveFieldName('field2')
        );
    }

    public function testResolveFieldTypeForFieldWithoutMapping()
    {
        self::assertEquals(
            'decimal',
            $this->fieldResolver->resolveFieldType('field2')
        );
    }

    public function testResolveFieldTypeWhenFieldTypeIsNull()
    {
        self::assertEquals(
            'text',
            $this->fieldResolver->resolveFieldType('field3')
        );
    }

    public function testResolveFieldTypeWhenFieldTypeIsNotDefined()
    {
        self::assertEquals(
            'text',
            $this->fieldResolver->resolveFieldType('field4')
        );
    }

    public function testResolveFieldNameForFieldWithPlaceholder()
    {
        self::assertEquals(
            'price_item',
            $this->fieldResolver->resolveFieldName('price_item')
        );
    }

    public function testResolveFieldTypeForFieldWithPlaceholder()
    {
        self::assertEquals(
            'decimal',
            $this->fieldResolver->resolveFieldType('price_item')
        );
    }

    public function testResolveFieldNameForFieldNotDefinedInSearchFieldMappings()
    {
        self::assertEquals(
            'another_field',
            $this->fieldResolver->resolveFieldName('another')
        );
    }

    public function testResolveFieldTypeForFieldNotDefinedInSearchFieldMappings()
    {
        self::assertEquals(
            'text',
            $this->fieldResolver->resolveFieldType('another')
        );
    }

    public function testResolveFieldNameForFieldWithPlaceholderButNotDefinedInSearchFieldMappings()
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('The field "another_item" is not supported.');

        $this->fieldResolver->resolveFieldName('another_item');
    }

    public function testResolveFieldTypeForFieldWithPlaceholderButNotDefinedInSearchFieldMappings()
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('The field "another_item" is not supported.');

        $this->fieldResolver->resolveFieldType('another_item');
    }

    public function testResolveFieldNameForUndefinedField()
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('The field "someField" is not supported.');

        $this->fieldResolver->resolveFieldName('someField');
    }

    public function testResolveFieldTypeForUndefinedField()
    {
        $this->expectException(InvalidFilterException::class);
        $this->expectExceptionMessage('The field "someField" is not supported.');

        $this->fieldResolver->resolveFieldType('someField');
    }
}
