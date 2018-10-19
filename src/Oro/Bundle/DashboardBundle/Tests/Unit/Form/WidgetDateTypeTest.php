<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetDateType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class WidgetDateTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetDateType */
    protected $formType;

    public function setUp()
    {
        $this->formType = new WidgetDateType();
    }

    public function testBuildFormWithDate()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->exactly(2))
            ->method('add')
            ->will($this->returnSelf());
        $builder->expects($this->at(0))
            ->method('add')
            ->with('useDate', CheckboxType::class);
        $builder->expects($this->at(1))
            ->method('add')
            ->with('date', OroDateType::class);
        $this->formType->buildForm($builder, ['enable_date' => true]);
    }

    public function testBuildFormWithoutDate()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $builder->expects($this->once())
            ->method('add')
            ->with('useDate', CheckboxType::class)
            ->will($this->returnSelf());
        $this->formType->buildForm($builder, ['enable_date' => false]);
    }
}
