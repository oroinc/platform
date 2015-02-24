<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Generator;

use Oro\Bundle\LayoutBundle\Layout\Generator\GeneratorData;
use Oro\Bundle\LayoutBundle\Layout\Generator\ConfigLayoutUpdateGenerator;
use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;

class ConfigLayoutUpdateGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigLayoutUpdateGenerator */
    protected $generator;

    protected function setUp()
    {
        $this->generator = new ConfigLayoutUpdateGenerator();
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
            $this->setExpectedException('\LogicException', $exception);
        }

        $this->generator->generate('testClassName', new GeneratorData($data), new ConditionCollection());
    }

    /**
     * @return array
     */
    public function resourceDataProvider()
    {
        return [
            'invalid data'                    => [
                '$data'      => new \stdClass(),
                '$exception' => 'Invalid data given, expected array with key "actions"'
            ],
            'should contains actions'         => [
                '$data'      => [],
                '$exception' => 'Invalid data given, expected array with key "actions"'
            ],
            'should contains known actions'   => [
                '$data'      => [
                    'actions' => [
                        ['@addSuperPuper' => null]
                    ]
                ],
                '$exception' => 'Invalid action at position: 0, name: @addSuperPuper'
            ],
            'action name should start from @' => [
                '$data'      => [
                    'actions' => [
                        ['add' => null]
                    ]
                ],
                '$exception' => 'Invalid action at position: 0, name: add'
            ],
            'known action proceed'            => [
                '$data' => [
                    'actions' => [
                        ['@add' => null]
                    ]
                ],
            ]
        ];
    }

    // @codingStandardsIgnoreStart
    public function testGenerate()
    {
        $this->assertSame(
<<<CLASS
<?php

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
                                    'parent'    => null,
                                    'blockType' => 'root'
                                ]
                            ],
                            [
                                '@add' => [
                                    'id'        => 'header',
                                    'parent'    => 'root',
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
                    ]
                ),
                new ConditionCollection()
            )
        );
    }

    // @codingStandardsIgnoreEnd

    public function testShouldProcessCondition()
    {
        $collection = new ConditionCollection();
        $this->generator->generate(
            'testClassName',
            new GeneratorData(
                [
                    'actions'   => [],
                    'condition' => [['@true' => null]]
                ]
            ),
            $collection
        );

        $this->assertNotEmpty($collection);
        $this->assertContainsOnlyInstancesOf(
            'Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConfigExpressionCondition',
            $collection
        );
    }
}
