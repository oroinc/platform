<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\TranslatorDependencyPass;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class TranslatorDependencyPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container->register('oro_translation.database_translation.metadata.cache');
        $container->register('oro_translation.resource.cache');
        $container->register('oro_translation.strategy.provider');
        $container->register('oro_translation.provider.translation_domain');
        $container->register('event_dispatcher');
        $container->register('logger');

        $applicationState = $this->createMock(ApplicationState::class);
        $container->register('oro_distribution.handler.application_status');
        $container->set('oro_distribution.handler.application_status', $applicationState);

        $translator = new Definition();
        $translator->setPublic(false);

        $container->setDefinition('translator.default', $translator);

        $this->assertFalse($translator->isPublic());

        $compiler = new TranslatorDependencyPass();
        $compiler->process($container);

        $this->assertTrue($translator->isPublic());
        $this->assertEquals(Translator::class, $translator->getClass());
        $this->assertEquals(
            [
                ['setDatabaseMetadataCache', [new Reference('oro_translation.database_translation.metadata.cache')]],
                ['setResourceCache', [new Reference('oro_translation.resource.cache')]],
                ['setStrategyProvider', [new Reference('oro_translation.strategy.provider')]],
                ['setTranslationDomainProvider', [new Reference('oro_translation.provider.translation_domain')]],
                ['setEventDispatcher', [new Reference('event_dispatcher')]],
                ['setLogger', [new Reference('logger')]],
                ['setApplicationState', [new Reference('oro_distribution.handler.application_status')]],
                ['setMessageCatalogueSanitizer', [new Reference('oro_translation.message_catalogue_sanitizer')]],
            ],
            $translator->getMethodCalls()
        );
    }
}
