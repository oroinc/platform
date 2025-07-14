<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetItemsChoiceType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class WidgetItemsChoiceTypeTest extends TestCase
{
    private WidgetItemsChoiceType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->formType = new WidgetItemsChoiceType();
    }

    public function testGetParent(): void
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}
