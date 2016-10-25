<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroIconType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroIconTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OroIconType
     */
    private $type;

    protected function setUp()
    {
        $this->type = new OroIconType($this->getMock('Symfony\Component\HttpKernel\KernelInterface'));
    }

    public function testParameters()
    {
        $this->assertEquals('genemu_jqueryselect2_choice', $this->type->getParent());
        $this->assertEquals('oro_icon_select', $this->type->getName());
    }
}
