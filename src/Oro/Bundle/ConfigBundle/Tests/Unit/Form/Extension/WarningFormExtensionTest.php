<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ConfigBundle\Form\Extension\WarningFormExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WarningFormExtensionTest extends TestCase
{
    public function testGetExtendedTypes()
    {
        static::assertContains(FormType::class, WarningFormExtension::getExtendedTypes());
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects(static::once())
            ->method('setDefined')
            ->with([
                'warning'
            ]);

        (new WarningFormExtension())->configureOptions($resolver);
    }

    public function testBuildViewWithoutWarning()
    {
        $extension = new WarningFormExtension();

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $extension->buildView($view, $form, []);
        static::assertArrayNotHasKey('warning', $view->vars);
    }

    public function testBuildViewWithWarning()
    {
        $extension = new WarningFormExtension();

        $warningText = 'Test Warning';

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $extension->buildView($view, $form, ['warning' => $warningText]);
        static::assertSame($warningText, $view->vars['warning']);
    }
}
