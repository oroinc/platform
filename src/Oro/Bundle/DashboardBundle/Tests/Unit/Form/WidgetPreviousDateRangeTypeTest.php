<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetPreviousDateRangeType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class WidgetPreviousDateRangeTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetPreviousDateRangeType */
    protected $formType;

    public function setUp()
    {
        $this->formType = new WidgetPreviousDateRangeType();
    }

    public function testGetParent()
    {
        $this->assertEquals(CheckboxType::class, $this->formType->getParent());
    }
}
