<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener;

use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\TranslationBundle\EventListener\JsTranslationDumpDemoDataListener;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

class JsTranslationDumpDemoDataListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var JsTranslationDumper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jsTranslationDumper;

    /**
     * @var LanguageProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $languageProvider;

    /**
     * @var MigrationDataFixturesEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->jsTranslationDumper = $this->createMock(JsTranslationDumper::class);
        $this->languageProvider = $this->createMock(LanguageProvider::class);

        $this->event = $this->createMock(MigrationDataFixturesEvent::class);
    }

    public function testOnPostLoad(): void
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->event->expects($this->once())
            ->method('log')
            ->with('dump js translations files for locales: fr_FR.');

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguageCodes')
            ->willReturn(['en', 'fr_FR']);

        $this->jsTranslationDumper->expects($this->any())
            ->method('isTranslationFileExist')
            ->willReturnMap([
                ['en', true],
                ['fr_FR', false]
            ]);

        $this->jsTranslationDumper->expects($this->once())
            ->method('dumpTranslations')
            ->with(['fr_FR']);

        $listener = new JsTranslationDumpDemoDataListener($this->jsTranslationDumper, $this->languageProvider, true);
        $listener->onPostLoad($this->event);
    }

    public function testOnPostLoadWithoutRebuildTranslations(): void
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->languageProvider->expects($this->once())
            ->method('getAvailableLanguageCodes')
            ->willReturn(['en', 'fr_FR']);

        $this->jsTranslationDumper->expects($this->any())
            ->method('isTranslationFileExist')
            ->willReturnMap([
                ['en', true],
                ['fr_FR', true]
            ]);

        $this->jsTranslationDumper->expects($this->never())
            ->method('dumpTranslations');

        $listener = new JsTranslationDumpDemoDataListener($this->jsTranslationDumper, $this->languageProvider, true);
        $listener->onPostLoad($this->event);
    }

    public function testOnPostLoadWithNotInstalled(): void
    {
        $this->event->expects($this->never())
            ->method($this->anything());

        $this->languageProvider->expects($this->never())
            ->method($this->anything());

        $this->jsTranslationDumper->expects($this->never())
            ->method($this->anything());

        $listener = new JsTranslationDumpDemoDataListener($this->jsTranslationDumper, $this->languageProvider, false);
        $listener->onPostLoad($this->event);
    }

    public function testOnPostLoadWithNoDemoFixtures(): void
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->languageProvider->expects($this->never())
            ->method($this->anything());

        $this->jsTranslationDumper->expects($this->never())
            ->method($this->anything());

        $listener = new JsTranslationDumpDemoDataListener($this->jsTranslationDumper, $this->languageProvider, true);
        $listener->onPostLoad($this->event);
    }
}
