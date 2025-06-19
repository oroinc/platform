<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\FormBundle\Form\Extension\CollectionExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionExtensionTest extends TestCase
{
    private CollectionExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new CollectionExtension();
    }

    public function testConfigureOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(self::once())
            ->method('setDefault')
            ->with('add_label', 'oro.form.collection.add');

        $this->extension->configureOptions($resolver);
    }

    public function testBuildView(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = ['add_label' => 'Add Button'];

        $this->extension->buildView($view, $form, $options);

        self::assertEquals($options['add_label'], $view->vars['add_label']);
    }
}
