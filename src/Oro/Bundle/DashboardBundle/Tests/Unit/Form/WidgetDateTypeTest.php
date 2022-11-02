<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetDateType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class WidgetDateTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetDateType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new WidgetDateType();
    }

    public function testBuildFormWithDate()
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['useDate', CheckboxType::class],
                ['date', OroDateType::class]
            );

        $this->formType->buildForm($builder, ['enable_date' => true]);
    }

    public function testBuildFormWithoutDate()
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->once())
            ->method('add')
            ->with('useDate', CheckboxType::class)
            ->willReturnSelf();

        $this->formType->buildForm($builder, ['enable_date' => false]);
    }
}
