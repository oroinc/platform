<?php

namespace Oro\Component\PhpUtils\Tests\Unit;

use Oro\Component\PhpUtils\ReflectionClassHelper;

class ReflectionClassHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var ReflectionClassHelper */
    protected $utils;

    protected function setUp()
    {
        $this->utils = new ReflectionClassHelper(
            'Oro\Component\Layout\Tests\Unit\Extension\Theme\Stubs\StubLayoutBuilderInterface'
        );
    }

    protected function tearDown()
    {
        unset($this->utils);
    }

    /**
     * @dataProvider hasMethodDataProvider
     *
     * @param string $methodName
     * @param bool   $expectedResult
     */
    public function testHasMethod($methodName, $expectedResult)
    {
        $this->assertSame($expectedResult, $this->utils->hasMethod($methodName));
    }

    /**
     * @return array
     */
    public function hasMethodDataProvider()
    {
        return [
            'empty method name, should be handled as unknown' => [
                '$methodName'     => '',
                '$expectedResult' => false
            ],
            'known method add'                                => [
                '$methodName'     => 'add',
                '$expectedResult' => true
            ],
            'known method clear'                              => [
                '$methodName'     => 'clear',
                '$expectedResult' => true
            ],
            'unknown method'                                  => [
                '$methodName'     => 'testUnknown',
                '$expectedResult' => false
            ],
        ];
    }

    /**
     * @dataProvider argumentsDataProvider
     *
     * @param array       $arguments
     * @param bool        $expectedResult
     * @param null|string $expectedErrorMessage
     * @param string      $actionName
     */
    public function testIsValidArguments(array $arguments, $expectedResult, $expectedErrorMessage, $actionName = 'add')
    {
        $result = $this->utils->isValidArguments($actionName, $arguments);
        $this->assertSame($expectedErrorMessage, $this->utils->getLastError());
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function argumentsDataProvider()
    {
        return [
            'not enough arguments'                               => [
                '$arguments'            => [],
                '$expectedResult'       => false,
                '$expectedErrorMessage' => 'Missing required argument(s) for "add" method: id, parentId, blockType',
            ],
            'extra arguments given, not assoc list'              => [
                '$arguments'            => [1, 2, 3, 4, 5, 6, 7],
                '$expectedResult'       => false,
                '$expectedErrorMessage' => 'Number of arguments given greater than declared in "add" method',
            ],
            'extra arguments given, assoc list'                  => [
                '$arguments'            => ['id' => 'testId', 'extraKey' => 'unknown', 'extraKeyOneMore' => 1],
                '$expectedResult'       => false,
                '$expectedErrorMessage' => 'Unknown argument(s) for "add" method given: extraKey, extraKeyOneMore',
            ],
            'missing required arguments, assoc list'             => [
                '$arguments'            => ['id' => 'testId', 'parentId' => 'parent'],
                '$expectedResult'       => false,
                '$expectedErrorMessage' => 'Missing required argument(s) for "add" method: blockType',
            ],
            'missing required arguments, not assoc list'         => [
                '$arguments'            => ['testId', 'parent'],
                '$expectedResult'       => false,
                '$expectedErrorMessage' => '"add" method requires at least 3 argument(s) to be passed, 2 given',
            ],
            'all required args given, assoc'                     => [
                '$arguments'            => ['id' => 'testId', 'parentId' => 'parent', 'blockType' => 'type'],
                '$expectedResult'       => true,
                '$expectedErrorMessage' => null,
            ],
            'all required args given, not assoc'                 => [
                '$arguments'            => ['testId', 'parent', 'type'],
                '$expectedResult'       => true,
                '$expectedErrorMessage' => null,
            ],
            'all known args given including optional, assoc'     => [
                '$arguments'            => [
                    'id'        => 'testId',
                    'parentId'  => 'parent',
                    'blockType' => 'type',
                    'options'   => [],
                    'siblingId' => 'idOfSibling',
                    'prepend'   => true
                ],
                '$expectedResult'       => true,
                '$expectedErrorMessage' => null,
            ],
            'all known args given including optional, not assoc' => [
                '$arguments'            => ['testId', 'parent', 'type', [], 'idOfSibling', true],
                '$expectedResult'       => true,
                '$expectedErrorMessage' => null,
            ],
            'action without parameters'                          => [
                '$arguments'            => [],
                '$expectedResult'       => true,
                '$expectedErrorMessage' => null,
                '$actionName'           => 'clear'
            ]
        ];
    }

    /**
     * @dataProvider completeArgumentsDataProvider
     *
     * @param array $arguments
     * @param array $expectedResults
     */
    public function testCompleteArguments(array $arguments, array $expectedResults)
    {
        $this->utils->completeArguments('add', $arguments);

        $this->assertSame($expectedResults, $arguments);
    }

    /**
     * @return array
     */
    public function completeArgumentsDataProvider()
    {
        return [
            'arguments, not assoc list, not required to complete'            => [
                '$arguments'       => ['testId', 'parent', 'type'],
                '$expectedResults' => ['testId', 'parent', 'type'],
            ],
            'no optional arguments in assoc list'                            => [
                '$arguments'       => ['id' => 'testId', 'parentId' => 'parent', 'blockType' => 'type'],
                '$expectedResults' => ['id' => 'testId', 'parentId' => 'parent', 'blockType' => 'type'],
            ],
            'complete only optional arguments in assoc list'                 => [
                '$arguments'       => ['id' => 'testId', 'blockType' => 'type'],
                '$expectedResults' => ['id' => 'testId', 'blockType' => 'type'],
            ],
            'should complete optional arguments in assoc list and fix order' => [
                '$arguments'       => [
                    'id'        => 'testId',
                    'siblingId' => 'testSiblingId',
                    'blockType' => 'type',
                    'parentId'  => 'parent',
                ],
                '$expectedResults' => [
                    'id'        => 'testId',
                    'parentId'  => 'parent',
                    'blockType' => 'type',
                    'options'   => [],
                    'siblingId' => 'testSiblingId'
                ],
            ]
        ];
    }
}
