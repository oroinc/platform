<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroIconType;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroIconTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OroIconType
     */
    private $type;

    protected function setUp()
    {
        $this->type = new OroIconType($this->createMock('Symfony\Component\HttpKernel\KernelInterface'));
    }

    public function testParameters()
    {
        $this->assertEquals(Select2ChoiceType::class, $this->type->getParent());
        $this->assertEquals('oro_icon_select', $this->type->getName());
    }
}
