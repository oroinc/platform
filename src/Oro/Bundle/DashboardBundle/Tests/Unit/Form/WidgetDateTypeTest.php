<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetDateType;
use Oro\Bundle\FormBundle\Form\Type\OroDateType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilder;

class WidgetDateTypeTest extends TestCase
{
    private WidgetDateType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->formType = new WidgetDateType();
    }

    public function testBuildFormWithDate(): void
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

    public function testBuildFormWithoutDate(): void
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->once())
            ->method('add')
            ->with('useDate', CheckboxType::class)
            ->willReturnSelf();

        $this->formType->buildForm($builder, ['enable_date' => false]);
    }
}
