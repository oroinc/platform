<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QueryDefinitionUtilTest extends \PHPUnit\Framework\TestCase
{
    public function testEncodeAndDecodeDefinition()
    {
        $definition = ['columns' => [['name' => 'column1']]];
        $encodedDefinition = QueryDefinitionUtil::encodeDefinition($definition);
        self::assertSame($definition, QueryDefinitionUtil::decodeDefinition($encodedDefinition));
        self::assertSame($definition, QueryDefinitionUtil::safeDecodeDefinition($encodedDefinition));
    }

    public function testDecodeDefinitionForNullValue()
    {
        self::assertSame([], QueryDefinitionUtil::decodeDefinition(null));
    }

    public function testDecodeDefinitionForInvalidValue()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The query designer definition is not valid JSON: Syntax error.');
        QueryDefinitionUtil::decodeDefinition('invalid json');
    }

    public function testSafeDecodeDefinitionForNullValue()
    {
        self::assertSame([], QueryDefinitionUtil::safeDecodeDefinition(null));
    }

    public function testSafeDecodeDefinitionForInvalidValue()
    {
        self::assertSame([], QueryDefinitionUtil::safeDecodeDefinition('invalid json'));
    }

    public function testBuildColumnIdentifierForColumnWithoutFunction()
    {
        $column = ['name' => 'column1'];
        self::assertEquals(
            'column1',
            QueryDefinitionUtil::buildColumnIdentifier($column)
        );
    }

    public function testBuildColumnIdentifierForColumnWithEmptyFunctionDefinition()
    {
        $column = ['name' => 'column1', 'func' => []];
        self::assertEquals(
            'column1',
            QueryDefinitionUtil::buildColumnIdentifier($column)
        );
    }

    public function testBuildColumnIdentifierForColumnWithFunction()
    {
        $column = [
            'name' => 'column1',
            'func' => ['name' => 'func1', 'group_name' => 'group1', 'group_type' => 'group_type1']
        ];
        self::assertEquals(
            'column1(func1,group1,group_type1)',
            QueryDefinitionUtil::buildColumnIdentifier($column)
        );
    }

    public function testBuildColumnIdentifierForColumnWithFunctionWithoutGroupNameAndType()
    {
        $column = [
            'name' => 'column1',
            'func' => ['name' => 'func1']
        ];
        self::assertEquals(
            'column1(func1,,)',
            QueryDefinitionUtil::buildColumnIdentifier($column)
        );
    }

    public function testBuildColumnIdentifierForColumnWithFunctionWithoutGroupName()
    {
        $column = [
            'name' => 'column1',
            'func' => ['name' => 'func1', 'group_type' => 'group_type1']
        ];
        self::assertEquals(
            'column1(func1,,group_type1)',
            QueryDefinitionUtil::buildColumnIdentifier($column)
        );
    }

    public function testBuildColumnIdentifierForColumnWithFunctionWithoutGroupType()
    {
        $column = [
            'name' => 'column1',
            'func' => ['name' => 'func1', 'group_name' => 'group1']
        ];
        self::assertEquals(
            'column1(func1,group1,)',
            QueryDefinitionUtil::buildColumnIdentifier($column)
        );
    }
}
