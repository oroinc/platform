<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorDependencyPass;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TranslatorDependencyPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $translator = $container->register('translator.default')
            ->setPublic(false);

        $compiler = new TranslatorDependencyPass();
        $compiler->process($container);

        $this->assertTrue($translator->isPublic());
        $this->assertEquals(Translator::class, $translator->getClass());
        $this->assertEquals(
            [
                [
                    'setStrategyProvider',
                    [new Reference('oro_translation.strategy.provider')]
                ],
                [
                    'setResourceCache',
                    [new Reference('oro_translation.resource.cache')]
                ],
                [
                    'setLogger',
                    [new Reference('logger')]
                ],
                [
                    'setMessageCatalogueSanitizer',
                    [new Reference('oro_translation.message_catalogue_sanitizer')]
                ],
                [
                    'setSanitizationErrorCollection',
                    [new Reference('oro_translation.translation_message_sanitization_errors')]
                ],
                [
                    'setDynamicTranslationProvider',
                    [new Reference('oro_translation.dynamic_translation_provider')]
                ],
            ],
            $translator->getMethodCalls()
        );
    }
}
