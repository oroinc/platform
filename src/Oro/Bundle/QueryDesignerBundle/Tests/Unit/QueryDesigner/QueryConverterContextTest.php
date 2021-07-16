<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryConverterContext;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class QueryConverterContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryConverterContext */
    private $context;

    protected function setUp(): void
    {
        $this->context = new QueryConverterContext();
    }

    public function testReset()
    {
        $initialContext = clone $this->context;

        $this->context->init(new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition(['columns' => [['name' => 'column1']]])
        ));
        $this->context->setTableAlias('test_join_id', 'test_alias');
        $this->context->setColumnAlias('test_column_id', 'test_column_alias', 'test_column_name');
        $this->context->setAlias('test_alias1', 'test_alias2');
        $this->context->setQueryAliases(['test_alias3']);
        $this->context->setVirtualColumnExpression('column1', 'test_expr');
        $this->context->setVirtualColumnOptions('column1_join_id', ['key' => 'value']);
        $this->context->setVirtualRelationJoin('test_join_id', 'test_virtual_join_id');

        $this->context->reset();
        self::assertEquals($initialContext, $this->context);
    }

    public function testShouldNotBePossibleToInitWithoutEntity()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The entity must be specified.');

        $source = new QueryDesigner(
            '',
            QueryDefinitionUtil::encodeDefinition(['columns' => [['name' => 'column1']]])
        );
        $this->context->init($source);
    }

    public function testShouldNotBePossibleToInitWithoutColumns()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "columns" definition does not exist.');

        $source = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition(['key' => 'value'])
        );
        $this->context->init($source);
    }

    public function testShouldNotBePossibleToInitWithEmptyColumns()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "columns" definition must not be empty.');

        $source = new QueryDesigner(
            'Test\Entity',
            QueryDefinitionUtil::encodeDefinition(['columns' => []])
        );
        $this->context->init($source);
    }

    public function testInit()
    {
        $entity = 'Test\Entity';
        $definition = ['columns' => [['name' => 'column1']]];
        $source = new QueryDesigner($entity, QueryDefinitionUtil::encodeDefinition($definition));

        $this->context->init($source);

        self::assertSame($entity, $this->context->getRootEntity());
        self::assertSame($definition, $this->context->getDefinition());
    }

    public function testRootJoinId()
    {
        self::assertSame('', $this->context->getRootJoinId());
    }

    public function testRootTableAlias()
    {
        $alias = 'test_alias';
        $this->context->setRootTableAlias($alias);
        self::assertSame($alias, $this->context->getRootTableAlias());
        self::assertSame(['' => $alias], $this->context->getTableAliases());
        self::assertSame([$alias => ''], $this->context->getJoins());
    }

    public function testGetRootTableAliasWhenItWasNotSet()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The root table alias is not defined.');
        $this->context->getRootTableAlias();
    }

    public function testJoins()
    {
        self::assertSame([], $this->context->getJoins());

        $rootAlias = 'test_alias1';
        $rootJoinId = '';
        $alias = 'test_alias2';
        $joinId = 'test_join_id';
        $this->context->setRootTableAlias($rootAlias);
        $this->context->setTableAlias($joinId, $alias);

        self::assertSame([$rootAlias => $rootJoinId, $alias => $joinId], $this->context->getJoins());

        self::assertFalse($this->context->hasJoin($rootAlias));
        self::assertTrue($this->context->hasJoin($alias));
        self::assertFalse($this->context->hasJoin('unknown'));

        self::assertSame($rootJoinId, $this->context->findJoin($rootAlias));
        self::assertSame($joinId, $this->context->findJoin($alias));
        self::assertNull($this->context->findJoin('unknown'));

        self::assertSame($rootJoinId, $this->context->getJoin($rootAlias));
        self::assertSame($joinId, $this->context->getJoin($alias));
    }

    public function testGetJoinForUnknownAlias()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The join for the alias "unknown" is not defined.');
        $this->context->getJoin('unknown');
    }

    public function testGenerateTableAlias()
    {
        self::assertEquals('t1', $this->context->generateTableAlias());
        self::assertEquals('t2', $this->context->generateTableAlias());
    }

    public function testTableAliases()
    {
        self::assertSame([], $this->context->getTableAliases());

        $rootAlias = 'test_alias1';
        $rootJoinId = '';
        $alias = 'test_alias2';
        $joinId = 'test_join_id';
        $this->context->setRootTableAlias($rootAlias);
        $this->context->setTableAlias($joinId, $alias);

        self::assertSame([$rootJoinId => $rootAlias, $joinId => $alias], $this->context->getTableAliases());

        self::assertTrue($this->context->hasTableAlias($rootJoinId));
        self::assertTrue($this->context->hasTableAlias($joinId));
        self::assertFalse($this->context->hasTableAlias('unknown'));

        self::assertSame($rootAlias, $this->context->findTableAlias($rootJoinId));
        self::assertSame($alias, $this->context->findTableAlias($joinId));
        self::assertNull($this->context->findTableAlias('unknown'));

        self::assertSame($rootAlias, $this->context->getTableAlias($rootJoinId));
        self::assertSame($alias, $this->context->getTableAlias($joinId));
    }

    public function testGetTableAliasForUnknownJoinId()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The table alias for the join "unknown" is not defined.');
        $this->context->getTableAlias('unknown');
    }

    public function testGenerateColumnAlias()
    {
        self::assertEquals('c1', $this->context->generateColumnAlias());
        self::assertEquals('c1', $this->context->generateColumnAlias());

        $this->context->setColumnAlias('test_column_id', 'test_column_alias', 'test_column_name');
        self::assertEquals('c2', $this->context->generateColumnAlias());
        self::assertEquals('c2', $this->context->generateColumnAlias());
    }

    public function testColumnAliases()
    {
        self::assertSame([], $this->context->getColumnAliases());

        $columnId = 'test_column_id';
        $columnAlias = 'test_column_alias';
        $columnName = 'test_column_name';
        $this->context->setColumnAlias($columnId, $columnAlias, $columnName);

        self::assertSame([$columnId => $columnAlias], $this->context->getColumnAliases());

        self::assertTrue($this->context->hasColumnAlias($columnId));
        self::assertFalse($this->context->hasColumnAlias('unknown'));

        self::assertSame($columnAlias, $this->context->findColumnAlias($columnId));
        self::assertNull($this->context->findColumnAlias('unknown'));

        self::assertSame($columnAlias, $this->context->getColumnAlias($columnId));
    }

    public function testGetColumnAliasForUnknownColumnId()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The column alias for the column "unknown" is not defined.');
        $this->context->getColumnAlias('unknown');
    }

    public function testGetAndFindColumnId()
    {
        $columnId = 'test_column_id';
        $columnAlias = 'test_column_alias';
        $columnName = 'test_column_name';
        $this->context->setColumnAlias($columnId, $columnAlias, $columnName);

        self::assertSame($columnId, $this->context->getColumnId($columnAlias));

        self::assertSame($columnId, $this->context->findColumnId($columnAlias));
        self::assertNull($this->context->findColumnId('unknown'));
    }

    public function testGetColumnIdForUnknownColumnAlias()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The column identifier for the column alias "unknown" is not defined.');
        $this->context->getColumnId('unknown');
    }

    public function testColumnNames()
    {
        $columnId = 'test_column_id';
        $columnAlias = 'test_column_alias';
        $columnName = 'test_column_name';
        $this->context->setColumnAlias($columnId, $columnAlias, $columnName);

        self::assertTrue($this->context->hasColumnName($columnId));
        self::assertFalse($this->context->hasColumnName('unknown'));

        self::assertSame($columnName, $this->context->findColumnName($columnId));
        self::assertNull($this->context->findColumnName('unknown'));

        self::assertSame($columnName, $this->context->getColumnName($columnId));
    }

    public function testGetColumnNameForUnknownColumnId()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The column name for the column "unknown" is not defined.');
        $this->context->getColumnName('unknown');
    }

    public function testVirtualColumnExpressions()
    {
        $column = 'column1';
        $expr = 'test_expr';
        $this->context->setVirtualColumnExpression($column, $expr);

        self::assertTrue($this->context->hasVirtualColumnExpression($column));
        self::assertFalse($this->context->hasVirtualColumnExpression('unknown'));

        self::assertSame($expr, $this->context->getVirtualColumnExpression($column));
    }

    public function testGetVirtualColumnExpressionForUnknownColumn()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The virtual column expression for the column "unknown" is not defined.');
        $this->context->getVirtualColumnExpression('unknown');
    }

    public function testVirtualColumnOptions()
    {
        $columnJoinId = 'column_join_id';
        $options = ['option1' => 'value1'];
        $this->context->setVirtualColumnOptions($columnJoinId, $options);

        self::assertTrue($this->context->hasVirtualColumnOptions($columnJoinId));
        self::assertFalse($this->context->hasVirtualColumnOptions('unknown'));

        self::assertSame($options, $this->context->getVirtualColumnOptions($columnJoinId));

        self::assertTrue($this->context->hasVirtualColumnOption($columnJoinId, 'option1'));
        self::assertFalse($this->context->hasVirtualColumnOption($columnJoinId, 'unknown'));
        self::assertFalse($this->context->hasVirtualColumnOption('unknown', 'option1'));

        self::assertSame('value1', $this->context->getVirtualColumnOption($columnJoinId, 'option1'));
    }

    public function testGetVirtualColumnOptionsForUnknownColumn()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The virtual column options for the column "unknown" are not defined.');
        $this->context->getVirtualColumnOptions('unknown');
    }

    public function testGetVirtualColumnOptionForUnknownColumn()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The virtual column options for the column "unknown" are not defined.');
        $this->context->getVirtualColumnOptions('unknown');
    }

    public function testGetVirtualColumnOptionForUnknownOption()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The virtual column option "unknown" for the column "column_join_id" is not defined.'
        );
        $this->context->setVirtualColumnOptions('column_join_id', ['option1' => 'value1']);
        $this->context->getVirtualColumnOption('column_join_id', 'unknown');
    }

    public function testVirtualRelationJoins()
    {
        self::assertFalse($this->context->hasVirtualRelationJoins());

        $joinId = 'test_join_id';
        $virtualJoinId = 'test_virtual_join_id';
        $this->context->setVirtualRelationJoin($joinId, $virtualJoinId);

        self::assertTrue($this->context->hasVirtualRelationJoins());

        self::assertTrue($this->context->hasVirtualRelationJoin($joinId));
        self::assertFalse($this->context->hasVirtualRelationJoin('unknown'));

        self::assertSame($virtualJoinId, $this->context->getVirtualRelationJoin($joinId));

        self::assertSame($joinId, $this->context->findJoinByVirtualRelationJoin($virtualJoinId));
        self::assertNull($this->context->findJoinByVirtualRelationJoin('unknown'));
    }

    public function testGetVirtualRelationJoinForUnknownJoin()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The virtual relation join for the join "unknown" is not defined.');
        $this->context->getVirtualRelationJoin('unknown');
    }

    public function testAliases()
    {
        self::assertSame([], $this->context->getAliases());

        $alias = 'test_alias';
        $tableAlias = 'test_table_alias';
        $this->context->setAlias($alias, $tableAlias);

        self::assertSame([$alias => $tableAlias], $this->context->getAliases());

        self::assertTrue($this->context->hasAlias($alias));
        self::assertFalse($this->context->hasAlias('unknown'));

        self::assertSame($tableAlias, $this->context->getAlias($alias));
    }

    public function testGetAliasForUnknownJoinId()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The table alias for the alias "unknown" is not defined.');
        $this->context->getAlias('unknown');
    }

    public function testQueryAliases()
    {
        self::assertSame([], $this->context->getQueryAliases());

        $aliases = ['test_alias1', 'test_alias2'];
        $this->context->setQueryAliases($aliases);

        self::assertSame($aliases, $this->context->getQueryAliases());
    }
}
