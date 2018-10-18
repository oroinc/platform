<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroTextListType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class OroTextListTypeTest extends FormIntegrationTestCase
{
    public function testGetName()
    {
        $formType = new OroTextListType();
        $this->assertEquals('oro_textlist', $formType->getName());
    }

    public function testGetParent()
    {
        $formType = new OroTextListType();
        $this->assertEquals(TextType::class, $formType->getParent());
    }

    public function testBuildForm()
    {
        // test empty default data
        $form = $this->factory->create(OroTextListType::class, [], []);
        $form->submit([]);

        $view = $form->createView();
        $viewData = ['value' => ''];
        foreach ($viewData as $key => $value) {
            $this->assertArrayHasKey($key, $view->vars);
            $this->assertEquals($value, $view->vars[$key]);
        }

        // test model converter
        $form = $this->factory->create(OroTextListType::class);
        $form->submit('test,one');
        $this->assertEquals(['test', 'one'], $form->getData());
    }
}
