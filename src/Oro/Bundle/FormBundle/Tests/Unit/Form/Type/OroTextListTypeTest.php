<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\FormBundle\Form\Type\OroTextListType;

class OroTextListTypeTest extends FormIntegrationTestCase
{
    /** @var OroTextListType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new OroTextListType();
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->formType);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_textlist', $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('text', $this->formType->getParent());
    }

    public function testBuildForm()
    {
        // test empty default data
        $form = $this->factory->create($this->formType, [], []);
        $form->submit([]);

        $view = $form->createView();
        $viewData = ['value' => ''];
        foreach ($viewData as $key => $value) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($value, $view->vars[$key]);
        }

        // test model converter
        $form = $this->factory->create($this->formType);
        $form->submit('test,one');
        $this->assertEquals(['test', 'one'], $form->getData());
    }
}
