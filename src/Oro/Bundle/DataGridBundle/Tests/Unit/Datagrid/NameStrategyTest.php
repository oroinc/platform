<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\NameStrategy;

class NameStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NameStrategy
     */
    protected $nameStrategy;

    protected function setUp()
    {
        $this->nameStrategy = new NameStrategy();
    }

    /**
     * @dataProvider validGridNamesDataProvider
     */
    public function testParseGridNameWorks($name, $expectedGridName)
    {
        $this->assertEquals($expectedGridName, $this->nameStrategy->parseGridName($name));
    }

    /**
     * @dataProvider validGridNamesDataProvider
     */
    public function testParseGridScopeWorks($name, $expectedGridName, $expectedGridScope)
    {
        $this->assertEquals($expectedGridScope, $this->nameStrategy->parseGridScope($name));
    }

    /**
     * @dataProvider validGridNamesDataProvider
     */
    public function testBuildGridFullNameWorks($expectedFullName, $gridName, $gridScope)
    {
        $this->assertEquals(
            $gridScope ? $expectedFullName : $gridName,
            $this->nameStrategy->buildGridFullName($gridName, $gridScope)
        );
    }

    public function validGridNamesDataProvider()
    {
        return [
            [
                'test_grid:test_scope',
                'test_grid',
                'test_scope',
            ],
            [
                'test_grid',
                'test_grid',
                '',
            ],
            [
                'test_grid:',
                'test_grid',
                '',
            ],
        ];
    }

    /**
     * @dataProvider invalidGridNamesDataProvider
     */
    public function testParseGridNameFails($expectedMessage, $name)
    {
        $this->setExpectedException(
            'Oro\\Bundle\\DataGridBundle\\Exception\\InvalidArgumentException',
            $expectedMessage
        );
        $this->nameStrategy->parseGridName($name);
    }

    /**
     * @dataProvider invalidGridNamesDataProvider
     */
    public function testParseGridScopeFails($expectedMessage, $name)
    {
        $this->setExpectedException(
            'Oro\\Bundle\\DataGridBundle\\Exception\\InvalidArgumentException',
            $expectedMessage
        );
        $this->nameStrategy->parseGridScope($name);
    }

    /**
     * @dataProvider invalidGridNamesDataProvider
     */
    public function testBuildGridFullNameFails($expectedMessage, $name, $gridName, $gridScope)
    {
        $this->setExpectedException(
            'Oro\\Bundle\\DataGridBundle\\Exception\\InvalidArgumentException',
            $expectedMessage
        );
        $this->nameStrategy->buildGridFullName($gridName, $gridScope);
    }

    public function invalidGridNamesDataProvider()
    {
        return [
            'too many delimiters' => [
                'Grid name "test_grid:test_scope:test_scope" is invalid, ' .
                'it should not contain more than one occurrence of ":".',
                'test_grid:test_scope:test_scope',
                'test_grid',
                'test_scope:test_scope',
            ],
            'empty name' => [
                'Grid name ":test_scope" is invalid, name must be not empty.',
                ':test_scope',
                '',
                'test_scope',
            ],
        ];
    }
}
