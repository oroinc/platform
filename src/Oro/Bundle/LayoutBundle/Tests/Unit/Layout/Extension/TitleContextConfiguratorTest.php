<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\LayoutBundle\Layout\Extension\TitleContextConfigurator;

class TitleContextConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TitleContextConfigurator */
    protected $contextConfigurator;

    protected function setUp()
    {
        $this->contextConfigurator = new TitleContextConfigurator();
    }

    /**
     * @param mixed $value
     * @dataProvider configureContextDataProvider
     */
    public function testConfigureContext($value)
    {
        $context = new LayoutContext();
        $context['title'] = $value;

        $this->contextConfigurator->configureContext($context);
        $context->resolve();

        $this->assertEquals($value, $context['title']);
    }

    public function configureContextDataProvider()
    {
        return [
            'null' => [null],
            'string' => ['Some Title'],
            'array' => [['First Part', 'Second Part']]
        ];
    }
}
