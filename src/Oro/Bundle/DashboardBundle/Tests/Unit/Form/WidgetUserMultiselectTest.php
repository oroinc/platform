<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetUserMultiselect;

class WidgetUserMultiselectTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetUserMultiselect */
    protected $formType;

    public function setUp()
    {
        $emMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new WidgetUserMultiselect($emMock);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_type_widget_user_multiselect', $this->formType->getName());
    }
}
