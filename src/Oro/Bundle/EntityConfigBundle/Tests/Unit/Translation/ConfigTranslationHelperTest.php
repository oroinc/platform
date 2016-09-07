<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Translation;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;

class ConfigTranslationHelperTest extends \PHPUnit_Framework_TestCase
{
    const LOCALE = 'en';

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslationManager */
    protected $translationManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DynamicTranslationMetadataCache */
    protected $translationCache;

    /** @var ConfigTranslationHelper */
    protected $helper;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->translationCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translationManager = $this
            ->getMockBuilder(TranslationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new ConfigTranslationHelper(
            $this->translator,
            $this->translationCache,
            $this->translationManager
        );
    }

    protected function tearDown()
    {
        unset(
            $this->translator,
            $this->translationCache,
            $this->repository,
            $this->helper,
            $this->translationManager
        );
    }

    /**
     * @dataProvider isTranslationEqualDataProvider
     *
     * @param string $translation
     * @param string $key
     * @param string $value
     * @param bool $expected
     */
    public function testIsTranslationEqual($translation, $key, $value, $expected)
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with($key)
            ->willReturn($translation);

        $this->assertEquals($expected, $this->helper->isTranslationEqual($key, $value));
    }

    /**
     * @return array
     */
    public function isTranslationEqualDataProvider()
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

    /**
     * @dataProvider saveTranslationsDataProvider
     *
     * @param array $translations
     * @param string|null $key
     * @param string|null $value
     */
    public function testSaveTranslations(array $translations, $key = null, $value = null)
    {
        if ($translations) {
            $this->assertTranslationManagerCalled($key, $value);
            $this->assertTranslationServicesCalled();
        } else {
            $this->translationManager->expects($this->never())->method($this->anything());
            $this->translationCache->expects($this->never())->method($this->anything());
        }

        $this->helper->saveTranslations($translations);
    }

    /**
     * @return array
     */
    public function saveTranslationsDataProvider()
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

    /**
     * @param string $key
     * @param string $value
     */
    protected function assertTranslationManagerCalled($key, $value)
    {
        $trans = new Translation();

        $this->translationManager->expects($this->once())
            ->method('saveValue')
            ->with($key, $value, self::LOCALE, TranslationRepository::DEFAULT_DOMAIN, Translation::SCOPE_UI)
            ->willReturn($trans);

        $this->translationManager->expects($this->once())
            ->method('flush')
            ->with([$trans]);
    }

    protected function assertTranslationServicesCalled()
    {
        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn(self::LOCALE);

        $this->translationCache->expects($this->once())
            ->method('updateTimestamp')
            ->with(self::LOCALE);
    }
}
