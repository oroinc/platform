<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetDateType;

class WidgetDateTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetDateType */
    protected $formType;

    public function setUp()
    {
        $this->formType = new WidgetDateType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_type_widget_date', $this->formType->getName());
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->exactly(2))
            ->method('add')
            ->will($this->returnSelf());
        $builder->expects($this->at(0))
            ->method('add')
            ->with('useDate', 'checkbox');
        $builder->expects($this->at(1))
            ->method('add')
            ->with('date', 'oro_date');
        $this->formType->buildForm($builder, []);
    }
}
