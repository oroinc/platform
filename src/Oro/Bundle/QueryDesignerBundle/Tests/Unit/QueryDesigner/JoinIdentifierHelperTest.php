<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\QueryDesigner;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;

class JoinIdentifierHelperTest extends \PHPUnit\Framework\TestCase
{
    const ROOT_ENTITY = 'Acme\RootEntity';

    /** @var JoinIdentifierHelper */
    private $helper;

    protected function setUp()
    {
        $this->helper = new JoinIdentifierHelper(self::ROOT_ENTITY);
    }

    /**
     * @dataProvider buildJoinIdentifierProvider
     */
    public function testBuildJoinIdentifier(
        $join,
        $parentJoinId,
        $joinType,
        $conditionType,
        $condition,
        $expected
    ) {
        $result = $this->helper->buildJoinIdentifier(
            $join,
            $parentJoinId,
            $joinType,
            $conditionType,
            $condition
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider buildColumnJoinIdentifierProvider
     */
    public function testBuildColumnJoinIdentifier($columnName, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->buildColumnJoinIdentifier($columnName)
        );
    }

    /**
     * @dataProvider explodeColumnNameProvider
     */
    public function testExplodeColumnName($columnName, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->explodeColumnName($columnName)
        );
    }

    /**
     * @dataProvider explodeJoinIdentifierProvider
     */
    public function testExplodeJoinIdentifier($joinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->explodeJoinIdentifier($joinId)
        );
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot get parent join identifier for root table.
     */
    public function testGetParentJoinIdentifierForRootJoinId()
    {
        $this->helper->getParentJoinIdentifier('');
    }

    /**
     * @dataProvider getParentJoinIdentifierProvider
     */
    public function testGetParentJoinIdentifier($joinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->getParentJoinIdentifier($joinId)
        );
    }

    /**
     * @dataProvider buildSiblingJoinIdentifierProvider
     */
    public function testBuildSiblingJoinIdentifier($joinId, $joinByFieldName, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->buildSiblingJoinIdentifier($joinId, $joinByFieldName)
        );
    }

    /**
     * @dataProvider getEntityClassNameProvider
     */
    public function testGetEntityClassName($columnNameOrJoinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->getEntityClassName($columnNameOrJoinId)
        );
    }

    /**
     * @dataProvider getFieldNameProvider
     */
    public function testGetFieldName($columnNameOrJoinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->getFieldName($columnNameOrJoinId)
        );
    }

    /**
     * @dataProvider isUnidirectionalJoinProvider
     */
    public function testIsUnidirectionalJoin($joinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->isUnidirectionalJoin($joinId)
        );
    }

    /**
     * @dataProvider getJoinProvider
     */
    public function testGetJoin($joinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->getJoin($joinId)
        );
    }

    /**
     * @dataProvider getJoinTypeProvider
     */
    public function testGetJoinType($joinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->getJoinType($joinId)
        );
    }

    /**
     * @dataProvider getJoinConditionTypeProvider
     */
    public function testGetJoinConditionType($joinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->getJoinConditionType($joinId)
        );
    }

    /**
     * @dataProvider getJoinConditionProvider
     */
    public function testGetJoinCondition($joinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->getJoinCondition($joinId)
        );
    }

    /**
     * @dataProvider isUnidirectionalJoinWithConditionProvider
     */
    public function testIsUnidirectionalJoinWithCondition($joinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->isUnidirectionalJoin($joinId)
        );
    }

    /**
     * @dataProvider getUnidirectionalJoinEntityNameProvider
     */
    public function testGetUnidirectionalJoinEntityName($joinId, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->helper->getUnidirectionalJoinEntityName($joinId)
        );
    }

    public function buildJoinIdentifierProvider()
    {
        return [
            ['alias.fld', null, null, null, null, 'alias.fld'],
            ['alias.fld', 'parent', null, null, null, 'parent+alias.fld'],
            ['alias.fld', 'parent', 'left', null, null, 'parent+alias.fld|left'],
            ['alias.fld', 'parent', 'left', 'WITH', null, 'parent+alias.fld|left|WITH'],
            ['alias.fld', 'parent', null, 'WITH', null, 'parent+alias.fld||WITH'],
            ['alias.fld', 'parent', 'left', 'WITH', 'condition', 'parent+alias.fld|left|WITH|condition'],
            ['alias.fld', 'parent', 'left', null, 'condition', 'parent+alias.fld|left||condition'],
            ['alias.fld', 'parent', null, 'WITH', 'condition', 'parent+alias.fld||WITH|condition'],
            ['alias.fld', 'parent', null, null, 'condition', 'parent+alias.fld|||condition'],
        ];
    }

    public function buildColumnJoinIdentifierProvider()
    {
        return [
            ['column1', self::ROOT_ENTITY . '::column1'],
            ['column1+Acme\E2::column2', self::ROOT_ENTITY . '::column1+Acme\E2::column2'],
        ];
    }

    public function explodeColumnNameProvider()
    {
        return [
            ['column1', ['']],
            [
                'column1+Acme\E2::column2',
                [
                    'Acme\RootEntity::column1'
                ]
            ],
            [
                'column1+Acme\E2::column2+Acme\E3::column3',
                [
                    'Acme\RootEntity::column1',
                    'Acme\RootEntity::column1+Acme\E2::column2'
                ]
            ],
            [
                'column1+Acme\E2::Acme\E21::column2+Acme\E3::Acme\E31::column3',
                [
                    'Acme\RootEntity::column1',
                    'Acme\RootEntity::column1+Acme\E2::Acme\E21::column2'
                ]
            ],
            [
                'column1+Acme\E2::Acme\E21::column2|left|WITH|condition+Acme\E3::column3',
                [
                    'Acme\RootEntity::column1',
                    'Acme\RootEntity::column1+Acme\E2::Acme\E21::column2|left|WITH|condition'
                ]
            ],
        ];
    }

    public function explodeJoinIdentifierProvider()
    {
        return [
            ['', ['']],
            [
                'Acme\E1::column1',
                [
                    'Acme\E1::column1'
                ]
            ],
            [
                'Acme\E1::column1+Acme\E2::column2',
                [
                    'Acme\E1::column1',
                    'Acme\E1::column1+Acme\E2::column2'
                ]
            ],
            [
                'Acme\E1::column1+Acme\E2::column2+Acme\E3::column3',
                [
                    'Acme\E1::column1',
                    'Acme\E1::column1+Acme\E2::column2',
                    'Acme\E1::column1+Acme\E2::column2+Acme\E3::column3'
                ]
            ],
            [
                'Acme\E1::column1+Acme\E2::Acme\E21::column2+Acme\E3::Acme\E31::column3',
                [
                    'Acme\E1::column1',
                    'Acme\E1::column1+Acme\E2::Acme\E21::column2',
                    'Acme\E1::column1+Acme\E2::Acme\E21::column2+Acme\E3::Acme\E31::column3'
                ]
            ],
            [
                'Acme\E1::column1+Acme\E2::Acme\E21::column2|left|WITH|condition+Acme\E3::column3',
                [
                    'Acme\E1::column1',
                    'Acme\E1::column1+Acme\E2::Acme\E21::column2|left|WITH|condition',
                    'Acme\E1::column1+Acme\E2::Acme\E21::column2|left|WITH|condition+Acme\E3::column3'
                ]
            ],
        ];
    }

    public function getParentJoinIdentifierProvider()
    {
        return [
            ['Acme\E1::column1', ''],
            ['Acme\E1::column1+Acme\E2::column2', 'Acme\E1::column1'],
            [
                'Acme\E1::column1+Acme\E2::column2+Acme\E3::column3',
                'Acme\E1::column1+Acme\E2::column2'
            ],
            [
                'Acme\E1::column1+Acme\E2::Acme\E21::column2',
                'Acme\E1::column1'
            ],
            [
                'Acme\E1::column1+Acme\E2::Acme\E21::column2+Acme\E3::column3',
                'Acme\E1::column1+Acme\E2::Acme\E21::column2'
            ],
            [
                'Acme\E1::column1+Acme\E2::Acme\E21::column2|left|WITH|condition+Acme\E3::column3',
                'Acme\E1::column1+Acme\E2::Acme\E21::column2|left|WITH|condition'
            ],
        ];
    }

    public function buildSiblingJoinIdentifierProvider()
    {
        return [
            ['', 'siblingColumn', self::ROOT_ENTITY . '::siblingColumn'],
            ['Acme\E1::column1', 'siblingColumn', 'Acme\E1::siblingColumn'],
            [
                'Acme\E1::column1+Acme\E2::column2',
                'siblingColumn',
                'Acme\E1::column1+Acme\E2::siblingColumn'
            ],
            [
                'Acme\E1::column1+Acme\E2::column2+Acme\E3::column2',
                'siblingColumn',
                'Acme\E1::column1+Acme\E2::column2+Acme\E3::siblingColumn'
            ],
            [
                'Acme\E1::column1+Acme\E2::Acme\E21::column2+Acme\E3::column2',
                'siblingColumn',
                'Acme\E1::column1+Acme\E2::Acme\E21::column2+Acme\E3::siblingColumn'
            ],
            [
                'Acme\E1::column1+Acme\E2::Acme\E21::column2+Acme\E3::Acme\E31::column2',
                'siblingColumn',
                'Acme\E1::column1+Acme\E2::Acme\E21::column2+Acme\E3::siblingColumn'
            ],
            [
                'Acme\E1::column1+Acme\E2::Acme\E21::column2|left|WITH|condition+Acme\E3::Acme\E31::column2',
                'siblingColumn',
                'Acme\E1::column1+Acme\E2::Acme\E21::column2|left|WITH|condition+Acme\E3::siblingColumn'
            ],
            [
                'Acme\E1::column1+Acme\E2::Acme\E21::column2+Acme\E3::Acme\E31::column2|left|WITH|condition',
                'siblingColumn',
                'Acme\E1::column1+Acme\E2::Acme\E21::column2+Acme\E3::siblingColumn'
            ],
        ];
    }

    public function getEntityClassNameProvider()
    {
        return [
            // column names
            ['column1', self::ROOT_ENTITY],
            ['column1+Acme\E2::column2', 'Acme\E2'],
            ['column1+Acme\E2::column2+Acme\E3::column3', 'Acme\E3'],
            ['column1+Acme\E2::column2+Acme\E3::Acme\E31::column3', 'Acme\E31'],
            ['column1+Acme\E2::column2+Acme\E3::Acme\E31::column3|left|WITH|condition', 'Acme\E31'],
            // join ids
            ['', self::ROOT_ENTITY],
            ['Acme\E1::column1', 'Acme\E1'],
            ['Acme\E1::column1+Acme\E2::column2', 'Acme\E2'],
            ['Acme\E1::column1+Acme\E2::column2+Acme\E3::column3', 'Acme\E3'],
            ['Acme\E1::column1+Acme\E2::column2+Acme\E3::Acme\E31::column3', 'Acme\E31'],
            ['Acme\E1::column1+Acme\E2::column2+Acme\E3::Acme\E31::column3|left|WITH|condition', 'Acme\E31'],
            // joins without entity class
            ['alias.fld', null],
            ['alias.fld|', null],
            ['column1+alias.fld', null],
            ['column1+alias.fld|', null],
            ['Acme\E1::column1+alias.fld', null],
            ['Acme\E1::column1+alias.fld|', null],
        ];
    }

    public function getUnidirectionalJoinEntityNameProvider()
    {
        return [
            // column names
            ['column1', 'column1'],
            ['column1+Acme\E2::column2', 'column2'],
            ['column1+Acme\E2::column2+Acme\E3::column3', 'column3'],
            ['column1+Acme\E2::column2+Acme\E3::Acme\E31::column3', 'column3'],
            ['column1+Acme\E2::column2+Acme\E3::Acme\E31::column3|left|WITH|condition', 'column3'],
            // join ids
            ['', false],
            ['Acme\E1::column1', 'column1'],
            ['Acme\E1::column1+Acme\E2::column2', 'column2'],
            ['Acme\E1::column1+Acme\E2::column2+Acme\E3::column3', 'column3'],
            ['Acme\E1::column1+Acme\E2::column2+Acme\E3::Acme\E31::column3', 'column3'],
            ['Acme\E1::column1+Acme\E2::column2+Acme\E3::Acme\E31::column3|left|WITH|condition', 'column3'],
            // joins without entity class
            ['alias.fld', 'alias.fld'],
            ['alias.fld|', 'alias.fld'],
            ['column1+alias.fld', 'alias.fld'],
            ['column1+alias.fld|', 'alias.fld'],
            ['Acme\E1::column1+alias.fld', 'alias.fld'],
            ['Acme\E1::column1+alias.fld|', 'alias.fld'],
        ];
    }

    public function getFieldNameProvider()
    {
        return [
            // column names
            ['column1', 'column1'],
            ['column1|left|WITH|condition', 'column1'],
            ['column1+Acme\E2::column2', 'column2'],
            ['column1+Acme\E2::column2|left|WITH|condition', 'column2'],
            ['column1+Acme\E2::column2+Acme\E3::column3', 'column3'],
            ['column1+Acme\E2::column2+Acme\E3::Acme\E31::column3', 'column3'],
            ['column1+Acme\E2::column2+Acme\E3::Acme\E31::column3|left|WITH|condition', 'column3'],
            // join ids
            ['', ''],
            ['Acme\E1::column1', 'column1'],
            ['Acme\E1::column1|left|WITH|condition', 'column1'],
            ['Acme\E1::column1+Acme\E2::column2', 'column2'],
            ['Acme\E1::column1+Acme\E2::column2|left|WITH|condition', 'column2'],
            ['Acme\E1::column1+Acme\E2::column2+Acme\E3::column3', 'column3'],
            ['Acme\E1::column1+Acme\E2::column2+Acme\E3::Acme\E31::column3', 'column3'],
            ['Acme\E1::column1+Acme\E2::column2+Acme\E3::Acme\E31::column3|left|WITH|condition', 'column3'],
            // joins without entity class
            ['alias.fld', 'fld'],
            ['alias.fld|', 'fld'],
            ['column1+alias.fld', 'fld'],
            ['column1+alias.fld|', 'fld'],
            ['Acme\E1::column1+alias.fld', 'fld'],
            ['Acme\E1::column1+alias.fld|', 'fld'],
        ];
    }

    public function isUnidirectionalJoinProvider()
    {
        return [
            ['', false],
            ['Acme\E1::column1', false],
            ['Acme\E1::column1|left|WITH|condition', false],
            ['Acme\E1::column1+Acme\E2::column2', false],
            ['Acme\E1::column1+Acme\E2::column2|left|WITH|condition', false],
            ['Acme\E1::Acme\E11::column1', true],
            ['Acme\E1::Acme\E11::column1|left|WITH|condition', true],
            ['Acme\E1::column1+Acme\E2::Acme\E21::column2', true],
            ['Acme\E1::column1+Acme\E2::Acme\E21::column2|left|WITH|condition', true],
            ['Acme\E1::Acme\E11::column1+Acme\E2::column2', false],
            ['Acme\E1::Acme\E11::column1+Acme\E2::column2|left|WITH|condition', false],
            ['Acme\E1::Acme\E11::column1+Acme\E2::Acme\E21::column2', true],
            ['Acme\E1::Acme\E11::column1+Acme\E2::Acme\E21::column2|left|WITH|condition', true],
        ];
    }

    public function isUnidirectionalJoinWithConditionProvider()
    {
        return [
            ['', false],
            ['t1.country|left', false],
            ['Acme\E1::column1', false],
            ['Acme\E1::column1|left|WITH|condition', false],
            ['Acme\E1::column1+Acme\E2::column2', false],
            ['Acme\E1::column1+Acme\E2::column2|left|WITH|condition', false],
            ['Acme\E1::Acme\E11::column1', true],
            ['Acme\E1::Acme\E11::column1|left|WITH|condition', true],
            ['Acme\E1::column1+Acme\E2::Acme\E21::column2', true],
            ['Acme\E1::column1+Acme\E2::Acme\E21::column2|left|WITH|condition', true],
            ['Acme\E1::Acme\E11::column1+Acme\E2::column2', false],
            ['Acme\E1::Acme\E11::column1+Acme\E2::column2|left|WITH|condition', false],
            ['Acme\E1::Acme\E11::column1+Acme\E2::Acme\E21::column2', true],
            ['Acme\E1::Acme\E11::column1+Acme\E2::Acme\E21::column2|left|WITH|condition', true],
        ];
    }

    public function getJoinProvider()
    {
        return [
            ['', ''],
            ['Acme\E1::column1', 'Acme\E1::column1'],
            ['Acme\E1::column1|', 'Acme\E1::column1'],
            ['alias.fld', 'alias.fld'],
            ['alias.fld|', 'alias.fld'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2', 'Acme\E2::column2'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|', 'Acme\E2::column2'],
            ['Acme\E1::column1|inner|ON|condition+alias.fld', 'alias.fld'],
            ['Acme\E1::column1|inner|ON|condition+alias.fld|', 'alias.fld'],
        ];
    }

    public function getJoinTypeProvider()
    {
        return [
            ['', null],
            ['Acme\E1::column1', null],
            ['Acme\E1::column1|', null],
            ['Acme\E1::column1||', null],
            ['Acme\E1::column1|||', null],
            ['Acme\E1::column1||WITH', null],
            ['Acme\E1::column1||WITH|test.column = true', null],
            ['Acme\E1::column1|left', 'left'],
            ['Acme\E1::column1|left|', 'left'],
            ['Acme\E1::column1|left||', 'left'],
            ['Acme\E1::column1|left|WITH', 'left'],
            ['Acme\E1::column1|left|WITH|test.column = true', 'left'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2||', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|||', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2||WITH', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2||WITH|test.column = true', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left', 'left'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left|', 'left'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left||', 'left'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left|WITH', 'left'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left|WITH|test.column = true', 'left'],
        ];
    }

    public function getJoinConditionTypeProvider()
    {
        return [
            ['', null],
            ['Acme\E1::column1', null],
            ['Acme\E1::column1|', null],
            ['Acme\E1::column1||', null],
            ['Acme\E1::column1|||', null],
            ['Acme\E1::column1||WITH', 'WITH'],
            ['Acme\E1::column1||WITH|test.column = true', 'WITH'],
            ['Acme\E1::column1|left', null],
            ['Acme\E1::column1|left|', null],
            ['Acme\E1::column1|left||', null],
            ['Acme\E1::column1|left|WITH', 'WITH'],
            ['Acme\E1::column1|left|WITH|test.column = true', 'WITH'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2||', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|||', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2||WITH', 'WITH'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2||WITH|test.column = true', 'WITH'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left|', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left||', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left|WITH', 'WITH'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left|WITH|test.column = true', 'WITH'],
        ];
    }

    public function getJoinConditionProvider()
    {
        return [
            ['', null],
            ['Acme\E1::column1', null],
            ['Acme\E1::column1|', null],
            ['Acme\E1::column1||', null],
            ['Acme\E1::column1|||', null],
            ['Acme\E1::column1||WITH', null],
            ['Acme\E1::column1||WITH|test.column = true', 'test.column = true'],
            ['Acme\E1::column1|left', null],
            ['Acme\E1::column1|left|', null],
            ['Acme\E1::column1|left||', null],
            ['Acme\E1::column1|left|WITH', null],
            ['Acme\E1::column1|left|WITH|test.column = true', 'test.column = true'],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2||', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|||', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2||WITH', null],
            [
                'Acme\E1::column1|inner|ON|condition+Acme\E2::column2||WITH|test.column = true',
                'test.column = true'
            ],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left|', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left||', null],
            ['Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left|WITH', null],
            [
                'Acme\E1::column1|inner|ON|condition+Acme\E2::column2|left|WITH|test.column = true',
                'test.column = true'
            ],
        ];
    }
}
