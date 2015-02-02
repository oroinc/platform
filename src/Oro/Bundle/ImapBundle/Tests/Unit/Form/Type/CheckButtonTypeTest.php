<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;

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
        $btn  = $this->factory->create('oro_imap_configuration_check');
        $view = $btn->createView();

        $this->assertArrayHasKey('class', $view->vars['attr']);
    }
}
