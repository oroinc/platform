<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\FormBundle\DependencyInjection\Compiler\CaptchaProtectedFormsCompilerPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\AbstractArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class CaptchaProtectedFormsCompilerPassTest extends TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $registry = $container->register('oro_form.captcha.protected_forms_registry')
            ->setArgument('$protectedForms', new AbstractArgument());
        $container->register('form_1_service')
            ->addTag('form.type')
            ->addTag('oro_form.captcha_protected', ['form_name' => 'form_1']);
        $container->register('form_2_service')
            ->addTag('form.type')
            ->addTag('oro_form.captcha_protected', ['form_name' => 'form_2']);
        $container->register('form_3_service')
            ->addTag('form.type');

        $compiler = new CaptchaProtectedFormsCompilerPass();
        $compiler->process($container);

        self::assertEquals(['form_1', 'form_2'], $registry->getArgument('$protectedForms'));
    }

    public function testProcessWhenFormNameAttributeIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The attribute "form_name" is required for "oro_form.captcha_protected" tag. Service: "form_1_service".'
        );

        $container = new ContainerBuilder();
        $container->register('oro_form.captcha.protected_forms_registry')
            ->setArgument('$protectedForms', new AbstractArgument());
        $container->register('form_1_service')
            ->addTag('form.type')
            ->addTag('oro_form.captcha_protected');

        $compiler = new CaptchaProtectedFormsCompilerPass();
        $compiler->process($container);
    }
}
