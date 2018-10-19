<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetTitleType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class WidgetTitleTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetTitleType */
    protected $formType;

    public function setUp()
    {
        $this->formType = new WidgetTitleType();
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
            ->with('title', TextType::class);
        $builder->expects($this->at(1))
            ->method('add')
            ->with('useDefault', CheckboxType::class);
        $this->formType->buildForm($builder, []);
    }
}
