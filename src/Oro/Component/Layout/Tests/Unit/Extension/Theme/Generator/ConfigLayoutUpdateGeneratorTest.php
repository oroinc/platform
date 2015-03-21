<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\Generator;

use Oro\Component\ConfigExpression\Condition;

use Oro\Component\Layout\Extension\Theme\Generator\GeneratorData;
use Oro\Component\Layout\Extension\Theme\Generator\ConfigLayoutUpdateGenerator;

class ConfigLayoutUpdateGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $expressionAssembler;

    /** @var ConfigLayoutUpdateGenerator */
    protected $generator;

    protected function setUp()
    {
        $this->expressionAssembler = $this->getMock('Oro\Component\ConfigExpression\AssemblerInterface');

        $this->generator = new ConfigLayoutUpdateGenerator($this->expressionAssembler);
    }

    protected function tearDown()
    {
        unset($this->generator);
    }

    /**
     * @dataProvider resourceDataProvider
     *
     * @param mixed $data
     * @param bool  $exception
     */
    public function testShouldValidateData($data, $exception = false)
    {
        if (false !== $exception) {
            $this->setExpectedException('\Oro\Component\Layout\Exception\SyntaxException', $exception);
        }

        $this->generator->generate('testClassName', new GeneratorData($data));
    }

    /**
     * @return array
     */
    public function resourceDataProvider()
    {
        return [
            'invalid data'                                                   => [
                '$data'      => new \stdClass(),
                '$exception' => 'Syntax error: expected array with "actions" node at "."'
            ],
            'should contains actions'                                        => [
                '$data'      => [],
                '$exception' => 'Syntax error: expected array with "actions" node at "."'
            ],
            'should contains known actions'                                  => [
                '$data'      => [
                    'actions' => [
                        ['@addSuperPuper' => null]
                    ]
                ],
                '$exception' => 'Syntax error: unknown action "addSuperPuper", '
                    . 'should be one of LayoutManipulatorInterface\'s methods at "actions.0"'
            ],
            'should contains array with action definition in actions'        => [
                '$data'      => [
                    'actions' => ['some string']
                ],
                '$exception' => 'Syntax error: expected array with action name as key at "actions.0"'
            ],
            'action name should start from @'                                => [
                '$data'      => [
                    'actions' => [
                        ['add' => null]
                    ]
                ],
                '$exception' => 'Syntax error: action name should start with "@" symbol,'
                    . ' current name "add" at "actions.0"'
            ],
            'known action proceed'                                           => [
                '$data'      => [
                    'actions' => [
                        ['@add' => null]
                    ]
                ],
                '$exception' => '"add" action requires at least 3 argument(s) to be passed, 1 given at "actions.0"'
            ],
            '@addTree with invalid structure'                                => [
                '$data'      => [
                    'actions' => [
                        ['@addTree' => null]
                    ]
                ],
                '$exception' => 'expected array with keys "items" and "tree" at "actions.0"'
            ],
            '@addTree item not found in "items" list'                        => [
                '$data'      => [
                    'actions' => [
                        ['@addTree' => ['items' => [], 'tree' => ['root' => ['head' => null]]]]
                    ]
                ],
                '$exception' => 'invalid tree definition. Item with id "head" not found in items list at "actions.0"'
            ],
            '@addTree item has invalid definition, should show correct path' => [
                '$data'      => [
                    'actions' => [
                        [
                            '@add' => [
                                'id'        => 'root',
                                'parentId'  => null,
                                'blockType' => 'root'
                            ]
                        ],
                        [
                            '@addTree' => [
                                'items' => ['head' => ['test' => 1]],
                                'tree'  => ['root' => ['head' => null]]
                            ]
                        ]
                    ]
                ],
                '$exception' => 'Unknown argument(s) for "add" action given: test at "actions.1"'
            ],
        ];
    }

    // @codingStandardsIgnoreStart
    public function testGenerate()
    {
        $this->assertSame(
<<<CLASS
<?php

/**
 * Filename: testfilename.yml
 */
class testClassName implements \Oro\Component\Layout\LayoutUpdateInterface
{
    public function updateLayout(\Oro\Component\Layout\LayoutManipulatorInterface \$layoutManipulator, \Oro\Component\Layout\LayoutItemInterface \$item)
    {
        \$layoutManipulator->add( 'root', NULL, 'root' );
        \$layoutManipulator->add( 'header', 'root', 'header' );
        \$layoutManipulator->addAlias( 'header', 'header_alias' );
    }
}
CLASS
            ,
            $this->generator->generate(
                'testClassName',
                new GeneratorData(
                    [
                        'actions' => [
                            [
                                '@add' => [
                                    'id'        => 'root',
                                    'parentId'  => null,
                                    'blockType' => 'root'
                                ]
                            ],
                            [
                                '@add' => [
                                    'id'        => 'header',
                                    'parentId'  => 'root',
                                    'blockType' => 'header'
                                ]
                            ],
                            [
                                '@addAlias' => [
                                    'alias' => 'header',
                                    'id'    => 'header_alias',
                                ]
                            ]
                        ]
                    ],
                    'testfilename.yml'
                )
            )
        );
    }
    // @codingStandardsIgnoreEnd

    // @codingStandardsIgnoreStart
    public function testGenerateFormTree()
    {
        $this->assertSame(
<<<CLASS
<?php

/**
 * Filename: testfilename.yml
 */
class testClassName implements \Oro\Component\Layout\LayoutUpdateInterface
{
    public function updateLayout(\Oro\Component\Layout\LayoutManipulatorInterface \$layoutManipulator, \Oro\Component\Layout\LayoutItemInterface \$item)
    {
        \$layoutManipulator->add( 'header', 'root', 'header' );
        \$layoutManipulator->add( 'css', 'header', 'style' );
        \$layoutManipulator->add( 'body', 'root', 'content', array (
          'test' => true,
        ) );
        \$layoutManipulator->add( 'footer', 'root', 'header' );
        \$layoutManipulator->add( 'copyrights', 'footer', 'block' );
    }
}
CLASS
            ,
            $this->generator->generate(
                'testClassName',
                new GeneratorData(
                    [
                        'actions' => [
                            [
                                '@addTree' => [
                                    'items' => [
                                        'header' => [
                                            'blockType' => 'header'
                                        ],
                                        'css'    => [
                                            'style' // sequential declaration
                                        ],
                                        'body'   => [
                                            'blockType' => 'content',
                                            'options'   => [
                                                'test' => true
                                            ]
                                        ],
                                        'footer' => [
                                            'blockType' => 'header'
                                        ],
                                        'copyrights' => [
                                            'blockType' => 'block'
                                        ]
                                    ],
                                    'tree'  => [
                                        'root' => [
                                            'header' => ['css' => null],
                                            'body'   => null,
                                            'footer' => ['copyrights']  // sequential leafs
                                        ]
                                    ]
                                ]
                            ],
                        ]
                    ],
                    'testfilename.yml'
                )
            )
        );
    }
    // @codingStandardsIgnoreEnd

    public function testShouldProcessCondition()
    {
        $this->expressionAssembler->expects($this->once())
            ->method('assemble')
            ->with([['@true' => null]])
            ->willReturn(new Condition\True());

        $this->generator->generate(
            'testClassName',
            new GeneratorData(['actions' => [], 'conditions' => [['@true' => null]]])
        );

        $this->assertNotEmpty($this->generator->getVisitorCollection());
        $this->assertContainsOnlyInstancesOf(
            'Oro\Component\Layout\Extension\Theme\Generator\Visitor\ConfigExpressionConditionVisitor',
            $this->generator->getVisitorCollection()
        );
    }

    public function testShouldProcessConditionIfAssemblerReturnsNull()
    {
        $this->expressionAssembler->expects($this->once())
            ->method('assemble')
            ->with(['not supported'])
            ->willReturn(null);

        $this->generator->generate(
            'testClassName',
            new GeneratorData(
                [
                    'actions'    => [],
                    'conditions' => ['not supported']
                ]
            )
        );

        $this->assertEmpty($this->generator->getVisitorCollection());
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\SyntaxException
     * @expectedExceptionMessage Syntax error: invalid conditions. assembling failed at "conditions"
     */
    public function testShouldWrapAssemblerExceptionWithSyntaxException()
    {
        $this->expressionAssembler->expects($this->once())
            ->method('assemble')
            ->with(['error'])
            ->willThrowException(new \Exception('assembling failed'));

        $this->generator->generate(
            'testClassName',
            new GeneratorData(
                [
                    'actions'    => [],
                    'conditions' => ['error']
                ]
            )
        );
    }
}
