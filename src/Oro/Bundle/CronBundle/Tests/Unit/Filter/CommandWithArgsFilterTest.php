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
     * test Init
     */
    public function testInit()
    {
        $name = 'command';

        $params = [
            'type' => 'command_with_args',
            'data_name' => 'j.command',
            'label' => 'oro.cron.header.command',
            'enabled' => true,
            'translatable' => true,
            'fields' => ['j.command', 'j.args']
        ];

        $expectedParams = $params;
        $expectedParams['data_name'] = 'CONCAT(j.command,j.args)';

        $this->filter->init($name, $params);

        $this->assertEquals($name, $this->filter->getName());

        $reflection = new \ReflectionObject($this->filter);
        $reflectionParams = $reflection->getProperty('params');
        $reflectionParams->setAccessible(true);

        $this->assertEquals($expectedParams, $reflectionParams->getValue($this->filter));
    }

    /**
     * test Init Exception situation
     *
     * @expectedException InvalidArgumentException
     */
    public function testInitException()
    {
        $this->filter->init(null, []);
    }

    /**
     * test parseValue
     */
    public function testParseValue()
    {
        $reflection = new \ReflectionObject($this->filter);
        $reflectionMethod = $reflection->getMethod('parseValue');
        $reflectionMethod->setAccessible(true);
        $parseValue = $reflectionMethod->getClosure($this->filter);

        $this->assertEquals(
            'oro:process:execute:job["--id=1',
            $parseValue(TextFilterType::TYPE_EQUAL, 'oro:process:execute:job --id=1')
        );
    }
}
