<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetTitleType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder;

class WidgetTitleTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var WidgetTitleType */
    private $formType;

    protected function setUp(): void
    {
        $this->formType = new WidgetTitleType();
    }

    public function testBuildForm()
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
