<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Form;

use Oro\Bundle\UserBundle\Form\Type\WidgetUserSelectType;

class WidgetUserSelectTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetUserSelectType */
    protected $formType;

    public function setUp()
    {
        $emMock = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new WidgetUserSelectType($emMock);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_type_widget_user_select', $this->formType->getName());
    }
}
