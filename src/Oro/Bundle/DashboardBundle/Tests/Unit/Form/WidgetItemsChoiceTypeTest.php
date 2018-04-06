<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetItemsChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class WidgetItemsChoiceTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetItemsChoiceType */
    protected $formType;

    public function setUp()
    {
        $this->formType = new WidgetItemsChoiceType();
    }

    public function testGetName()
    {
        $this->assertEquals('oro_type_widget_items_choice', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}
