<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Filter;

use Oro\Bundle\CronBundle\Filter\CommandWithArgsFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;

class CommandWithArgsFilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CommandWithArgsFilter
     */
    protected $filter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->filter = new CommandWithArgsFilter(
            $this->getMock('Symfony\Component\Form\FormFactoryInterface'),
            $this->getMock('Oro\Bundle\FilterBundle\Filter\FilterUtility')
        );
    }

    /**
     * @dataProvider parseValueDataProvider
     * @param $value
     * @param $type
     * @param $expected
     */
    public function testParseValue($value, $type, $expected)
    {
        $reflection = new \ReflectionObject($this->filter);
        $reflectionMethod = $reflection->getMethod('parseValue');
        $reflectionMethod->setAccessible(true);
        $parseValue = $reflectionMethod->getClosure($this->filter);

        $this->assertEquals(
            $expected,
            $parseValue($type, $value)
        );
    }

    /**
     * @return array
     */
    public function parseValueDataProvider()
    {
        return [
            [
                'oro:process:execute:job --id=1',
                TextFilterType::TYPE_CONTAINS,
                [
                    '%oro:process:execute:job%',
                    '%--id=1%',
                ]
            ],
            [
                'oro:process:execute:job --id=1 --id=2',
                TextFilterType::TYPE_NOT_CONTAINS,
                [
                    '%oro:process:execute:job%',
                    '%--id=1%',
                    '%--id=2%',
                ]
            ],
            [
                'oro:process:execute:job --id=1 --id=2',
                TextFilterType::TYPE_EQUAL,
                'oro:process:execute:job --id=1 --id=2'
            ]
        ];
    }
}
