<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetPreviousDateRangeType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class WidgetPreviousDateRangeTypeTest extends TestCase
{
    private WidgetPreviousDateRangeType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->formType = new WidgetPreviousDateRangeType();
    }

    public function testGetParent(): void
    {
        $this->assertEquals(CheckboxType::class, $this->formType->getParent());
    }
}
