<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class CheckButtonTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $checkButtonType = new CheckButtonType();

        return [
            new PreloadedExtension(
                [
                    $checkButtonType->getName() => $checkButtonType
                ],
                []
            )
        ];
    }

    public function testForm()
    {
        $btn  = $this->factory->create(CheckButtonType::class);
        $view = $btn->createView();

        $this->assertArrayHasKey('class', $view->vars['attr']);
    }
}
