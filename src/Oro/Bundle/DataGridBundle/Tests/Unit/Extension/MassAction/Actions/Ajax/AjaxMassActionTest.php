<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction\Actions\Ajax;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;
use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\AjaxMassAction;

class AjaxMassActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AjaxMassAction
     */
    protected $action;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->action = new AjaxMassAction();
    }

    /**
     * @param array $source
     * @param array $expected
     * @dataProvider setOptionsDataProvider
     */
    public function testSetOptions(array $source, array $expected)
    {
        $this->action->setOptions(ActionConfiguration::create($source));

        $actual = $this->action->getOptions();
        foreach ($expected as $name => $value) {
            $this->assertEquals($value, $actual->offsetGet($name));
        }
    }

    /**
     * @return array
     */
    public function setOptionsDataProvider()
    {
        return [
            'confirmation is empty' => [
                'source' => [
                    'handler' => 'test.handler',
                ],
                'expected' => [
                    'confirmation' => true,
                ],
            ],
            'confirmation is false' => [
                'source' => [
                    'handler' => 'test.handler',
                    'confirmation' => false,
                ],
                'expected' => [
                    'confirmation' => false,
                ],
            ],
        ];
    }
}
