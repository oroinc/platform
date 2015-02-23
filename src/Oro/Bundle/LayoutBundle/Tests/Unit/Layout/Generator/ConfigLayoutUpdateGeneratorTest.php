<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Generator;

use Oro\Bundle\LayoutBundle\Layout\Generator\Condition\ConditionCollection;
use Oro\Bundle\LayoutBundle\Layout\Generator\ConfigLayoutUpdateGenerator;

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

        $this->generator->generate('testClassName', $data, new ConditionCollection());
    }

    /**
     * @return array
     */
    public function resourceDataProvider()
    {
        return [
            'invalid data'                  => [
                '$data'      => new \stdClass(),
                '$exception' => 'Invalid data given, expected array with key "actions"'
            ],
            'should contains actions'       => [
                '$data'      => [],
                '$exception' => 'Invalid data given, expected array with key "actions"'
            ],
            'should contains known actions' => [
                '$data'      => [
                    'actions' => [
                        ['@addSuperPuper' => null]
                    ]
                ],
                '$exception' => 'Invalid action at position: 0, name: @addSuperPuper'
            ],
        ];
    }
}
