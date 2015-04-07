<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetTitleType;

class WidgetTitleTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetTitleType */
    protected $formType;

    public function setUp()
    {
        $this->formType = new WidgetTitleType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_type_widget_title', $this->formType->getName());
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
            ->with('title', 'text');
        $builder->expects($this->at(1))
            ->method('add')
            ->with('useDefault', 'checkbox');
        $this->formType->buildForm($builder, []);
    }
}
