<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\EventListener\ClearDynamicTranslationCacheListener;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationCache;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class ClearDynamicTranslationCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DynamicTranslationCache|\PHPUnit\Framework\MockObject\MockObject */
    private $dynamicTranslationCache;

    /** @var ClearDynamicTranslationCacheListener */
    private $listener;

    protected function setUp(): void
    {
        $this->dynamicTranslationCache = $this->createMock(DynamicTranslationCache::class);

        $container = TestContainerBuilder::create()
            ->add(DynamicTranslationCache::class, $this->dynamicTranslationCache)
            ->getContainer($this);

        $this->listener = new ClearDynamicTranslationCacheListener($container);
    }

    private function getLanguage(string $code): Language
    {
        $language = new Language();
        $language->setCode($code);

        return $language;
    }

    public function testOnTranslationChanged(): void
    {
        $language1 = $this->getLanguage('en_US');
        $language2 = $this->getLanguage('en');
        $translation1 = new Translation();
        $translation1->setLanguage($language1);
        $translation2 = new Translation();
        $translation2->setLanguage($language1);
        $translation3 = new Translation();
        $translation3->setLanguage($language2);
        $translation4 = new Translation();

        $this->listener->onTranslationChanged($translation1);
        $this->listener->onTranslationChanged($translation2);
        $this->listener->onTranslationChanged($translation3);
        $this->listener->onTranslationChanged($translation4);
        $this->listener->onTranslationChanged($translation1);

        $this->dynamicTranslationCache->expects(self::once())
            ->method('delete')
            ->with(['en_US', 'en']);

        $this->listener->postFlush();
    }

    public function testOnLanguageChanged(): void
    {
        $language1 = $this->getLanguage('en_US');
        $language2 = $this->getLanguage('en');

        $this->listener->onLanguageChanged($language1);
        $this->listener->onLanguageChanged($language2);
        $this->listener->onLanguageChanged($language1);

        $this->dynamicTranslationCache->expects(self::once())
            ->method('delete')
            ->with(['en_US', 'en']);

        $this->listener->postFlush();
    }

    public function testOnLocalizationChanged(): void
    {
        $language1 = $this->getLanguage('en_US');
        $language2 = $this->getLanguage('en');
        $localization1 = new Localization();
        $localization1->setLanguage($language1);
        $localization2 = new Localization();
        $localization2->setLanguage($language1);
        $localization3 = new Localization();
        $localization3->setLanguage($language2);
        $localization4 = new Localization();

        $this->listener->onLocalizationChanged($localization1);
        $this->listener->onLocalizationChanged($localization2);
        $this->listener->onLocalizationChanged($localization3);
        $this->listener->onLocalizationChanged($localization4);
        $this->listener->onLocalizationChanged($localization1);

        $this->dynamicTranslationCache->expects(self::once())
            ->method('delete')
            ->with(['en_US', 'en']);

        $this->listener->postFlush();
    }

    public function testPostFlushShouldClearScheduledLocales(): void
    {
        $language1 = $this->getLanguage('en_US');
        $this->listener->onLanguageChanged($language1);

        $this->dynamicTranslationCache->expects(self::once())
            ->method('delete')
            ->with(['en_US']);

        $this->listener->postFlush();

        // test that scheduledLocales is empty
        $this->listener->postFlush();
    }

    public function testOnClearShouldClearScheduledLocales(): void
    {
        $language1 = $this->getLanguage('en_US');
        $this->listener->onLanguageChanged($language1);

        $this->dynamicTranslationCache->expects(self::never())
            ->method('delete');

        $this->listener->onClear();

        // test that scheduledLocales is empty
        $this->listener->postFlush();
    }
}
