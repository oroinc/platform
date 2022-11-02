<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Translation;

use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class ConfigTranslationHelperTest extends \PHPUnit\Framework\TestCase
{
    private const LOCALE = 'en';

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslationManager */
    private $translationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Translator */
    private $translator;

    /** @var ConfigTranslationHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(Translator::class);
        $this->translationManager = $this->createMock(TranslationManager::class);

        $this->helper = new ConfigTranslationHelper(
            $this->translationManager,
            $this->translator
        );
    }

    /**
     * @dataProvider isTranslationEqualDataProvider
     */
    public function testIsTranslationEqual(string $translation, string $key, string $value, bool $expected)
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($key)
            ->willReturn($translation);

        $this->assertEquals($expected, $this->helper->isTranslationEqual($key, $value));
    }

    public function isTranslationEqualDataProvider(): array
    {
        return [
            'equal' => [
                'translation' => 'valid translation',
                'key' => 'test',
                'value' => 'valid translation',
                'expected' => true
            ],
            'not equal' => [
                'translation' => 'valid translation',
                'key' => 'test',
                'value' => 'invalid value',
                'expected' => false
            ]
        ];
    }

    public function testInvalidateCache()
    {
        $locale = 'en';

        $this->translationManager->expects($this->once())
            ->method('invalidateCache')
            ->with($locale);

        $this->helper->invalidateCache($locale);
    }

    /**
     * @dataProvider saveTranslationsDataProvider
     */
    public function testSaveTranslations(array $translations, string $key = null, string $value = null)
    {
        if ($translations) {
            $this->assertTranslationManagerCalled($key, $value);
            $this->assertTranslationServicesCalled();
        } else {
            $this->translationManager->expects($this->never())
                ->method($this->anything());
        }

        $this->helper->saveTranslations($translations);
    }

    public function saveTranslationsDataProvider(): array
    {
        $key = 'test.domain.label';
        $value = 'translation label';

        return [
            [
                'translations' => []
            ],
            [
                'translations' => [$key => $value],
                'key' => $key,
                'value' => $value
            ],
        ];
    }

    private function assertTranslationManagerCalled(string $key, string $value)
    {
        $trans = new Translation();

        $this->translationManager->expects($this->once())
            ->method('saveTranslation')
            ->with($key, $value, self::LOCALE, TranslationManager::DEFAULT_DOMAIN)
            ->willReturn($trans);

        $this->translationManager->expects($this->once())
            ->method('invalidateCache')
            ->with(self::LOCALE);

        $this->translationManager->expects($this->once())
            ->method('flush');
    }

    private function assertTranslationServicesCalled()
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn(self::LOCALE);
    }

    public function testTranslateWithFallbackTranslationExists()
    {
        $id = 'string';
        $translation = 'translation';
        $fallback = 'fallback';

        $this->translator->expects(self::once())
            ->method('hasTrans')
            ->with($id)
            ->willReturn(true);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with($id)
            ->willReturn($translation);

        self::assertEquals($translation, $this->helper->translateWithFallback($id, $fallback));
    }

    public function testTranslateWithFallbackTranslationDoesntExist()
    {
        $id = 'string';
        $fallback = 'fallback';

        $this->translator->expects(self::once())
            ->method('hasTrans')
            ->with($id)
            ->willReturn(false);

        $this->translator->expects(self::never())
            ->method('trans');

        self::assertEquals($fallback, $this->helper->translateWithFallback($id, $fallback));
    }
}
