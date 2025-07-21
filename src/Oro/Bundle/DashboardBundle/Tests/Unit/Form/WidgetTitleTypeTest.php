<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetTitleType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

class WidgetTitleTypeTest extends TestCase
{
    private WidgetTitleType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->formType = new WidgetTitleType();
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilder::class);
        $builder->expects($this->exactly(2))
            ->method('add')
            ->withConsecutive(
                ['title', TextType::class],
                ['useDefault', CheckboxType::class]
            );

        $this->formType->buildForm($builder, []);
    }
}
