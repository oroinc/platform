<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QueryDefinitionUtilTest extends TestCase
{
    public function testEncodeAndDecodeDefinition(): void
    {
        $definition = ['columns' => [['name' => 'column1']]];
        $encodedDefinition = QueryDefinitionUtil::encodeDefinition($definition);
        self::assertSame($definition, QueryDefinitionUtil::decodeDefinition($encodedDefinition));
        self::assertSame($definition, QueryDefinitionUtil::safeDecodeDefinition($encodedDefinition));
    }

    public function testDecodeDefinitionForNullValue(): void
    {
        self::assertSame([], QueryDefinitionUtil::decodeDefinition(null));
    }

    public function testDecodeDefinitionForInvalidValue(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The query designer definition is not valid JSON: Syntax error.');
        QueryDefinitionUtil::decodeDefinition('invalid json');
    }

    public function testSafeDecodeDefinitionForNullValue(): void
    {
        self::assertSame([], QueryDefinitionUtil::safeDecodeDefinition(null));
    }

    public function testSafeDecodeDefinitionForInvalidValue(): void
    {
        self::assertSame([], QueryDefinitionUtil::safeDecodeDefinition('invalid json'));
    }

    public function testBuildColumnIdentifierForColumnWithoutFunction(): void
    {
        $column = ['name' => 'column1'];
        self::assertEquals(
            'column1',
            QueryDefinitionUtil::buildColumnIdentifier($column)
        );
    }

    public function testBuildColumnIdentifierForColumnWithEmptyFunctionDefinition(): void
    {
        $column = ['name' => 'column1', 'func' => []];
        self::assertEquals(
            'column1',
            QueryDefinitionUtil::buildColumnIdentifier($column)
        );
    }

    public function testBuildColumnIdentifierForColumnWithFunction(): void
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

    public function testBuildColumnIdentifierForColumnWithFunctionWithoutGroupNameAndType(): void
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

    public function testBuildColumnIdentifierForColumnWithFunctionWithoutGroupName(): void
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

    public function testBuildColumnIdentifierForColumnWithFunctionWithoutGroupType(): void
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
