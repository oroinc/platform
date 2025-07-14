<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Formatter\LanguageCodeFormatter;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalizationChoicesProviderTest extends TestCase
{
    use EntityTrait;

    private LocalizationManager&MockObject $localizationManager;
    private LocaleSettings&MockObject $localeSettings;
    private LanguageProvider&MockObject $languageProvider;
    private LocalizationChoicesProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $languageFormatter = $this->createMock(LanguageCodeFormatter::class);
        $languageFormatter->expects($this->any())
            ->method('formatLocale')
            ->willReturnCallback(function ($code) {
                return 'formatted_' . $code;
            });

        $this->languageProvider = $this->createMock(LanguageProvider::class);

        $this->provider = new LocalizationChoicesProvider(
            $this->localeSettings,
            $languageFormatter,
            $this->languageProvider,
            $this->localizationManager
        );
    }

    /**
     * @dataProvider getLanguageChoicesDataProvider
     */
    public function testGetLanguageChoices(bool $onlyEnabled): void
    {
        $data = [
            $this->getEntity(Language::class, ['id' => 105, 'code' => 'en']),
            $this->getEntity(Language::class, ['id' => 110, 'code' => 'de'])
        ];

        $this->languageProvider->expects($this->once())
            ->method('getLanguages')
            ->with($onlyEnabled)
            ->willReturn($data);

        $this->assertEquals(
            [
                'formatted_en' => 105,
                'formatted_de' => 110,
            ],
            $this->provider->getLanguageChoices($onlyEnabled)
        );
    }

    public function getLanguageChoicesDataProvider(): array
    {
        return [
            'all languages' => ['onlyEnabled' => false],
            'only enabled languages' => ['onlyEnabled' => true]
        ];
    }

    public function testGetFormattingChoices(): void
    {
        $this->assertConfigManagerCalled();

        $choices = $this->provider->getFormattingChoices();

        $this->assertIsArray($choices);
        $this->assertContains('br_FR', $choices);
        $this->assertNotContains('ho', $choices);
        $this->assertEquals('br_FR', $choices['bretón (Francia)']);
    }

    public function testGetLocalizationChoices(): void
    {
        $entity1 = $this->getEntity(Localization::class, ['id' => 100, 'name' => 'test1']);
        $entity2 = $this->getEntity(Localization::class, ['id' => 42, 'name' => 'test2']);

        $this->localizationManager->expects($this->once())
            ->method('getLocalizations')
            ->with(null)
            ->willReturn([$entity1, $entity2]);

        $this->assertEquals(
            [
                $entity1->getName() => $entity1->getId(),
                $entity2->getName() => $entity2->getId(),
            ],
            $this->provider->getLocalizationChoices()
        );
    }

    private function assertConfigManagerCalled(): void
    {
        $this->localeSettings->expects($this->once())
            ->method('getLanguage')
            ->willReturn('es');
    }
}
