<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Model;

use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;

class WidgetOptionBagTest extends \PHPUnit\Framework\TestCase
{
    public function testExistingOptionsOfOptionsBag()
    {
        $options = [
            'option1' => 'val',
            'option2' => null,
        ];
        $bag = new WidgetOptionBag($options);

        $this->assertTrue($bag->has('option1'));
        $this->assertEquals('val', $bag->get('option1'));

        $this->assertTrue($bag->has('option2'));
        $this->assertNull($bag->get('option2'));

        $this->assertEquals($options, $bag->all());
    }

    public function testNonExistingOptionsOfOptionsBag()
    {
        $bag = new WidgetOptionBag([
            'option1' => 'val',
            'option2' => null,
        ]);

        $this->assertFalse($bag->has('option3'));
        $this->assertNull($bag->get('option3'));
        $this->assertEquals('def', $bag->get('option3', 'def'));
    }
}
