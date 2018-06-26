<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Helper\LanguageHelper;
use Oro\Bundle\TranslationBundle\Provider\ExternalTranslationsProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider;

class ExternalTranslationsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationServiceProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $serviceProvider;

    /** @var LanguageHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $languageHelper;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var ExternalTranslationsProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->serviceProvider = $this->createMock(TranslationServiceProvider::class);

        $this->languageHelper = $this->createMock(LanguageHelper::class);

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->provider = new ExternalTranslationsProvider(
            $this->serviceProvider,
            $this->languageHelper,
            $this->doctrineHelper
        );
    }

    /**
     * @param mixed $return
     * @param bool $expected
     *
     * @dataProvider hasTranslationsDataProvider
     */
    public function testHasTranslation($return, $expected)
    {
        $language = $this->createMock(Language::class);

        $this->languageHelper->expects($this->once())
            ->method('isTranslationsAvailable')
            ->with($language)
            ->willReturn($return);

        $this->assertEquals($expected, $this->provider->hasTranslations($language));
    }

    /**
     * @param mixed $return
     * @param bool $expected
     *
     * @dataProvider hasTranslationsDataProvider
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function testUpdateTranslations($return, $expected)
    {
        $language = $this->createMock(Language::class);
        $language->expects($this->any())->method('getCode')->willReturn('test_code');

        $this->languageHelper->expects($this->once())
            ->method('isTranslationsAvailable')
            ->with($language)
            ->willReturn($return);

        $this->languageHelper
            ->expects($return ? $this->once() : $this->never())
            ->method('downloadLanguageFile')
            ->with('test_code')
            ->willReturn('test_path');

        $this->serviceProvider->expects($return ? $this->once() : $this->never())
            ->method('loadTranslatesFromFile')
            ->with('test_path', 'test_code')
            ->willReturn(true);

        $date = new \DateTime();
        $this->languageHelper->expects($return ? $this->once() : $this->never())
            ->method('getLanguageStatistic')
            ->with('test_code')
            ->willReturn(['lastBuildDate' => $date]);

        $language->expects($return ? $this->once() : $this->never())->method('setInstalledBuildDate')->with($date);

        if ($return) {
            $manager = $this->createMock(EntityManager::class);
            $manager->expects($this->once())->method('flush')->with($language);
            $this->doctrineHelper->expects($this->once())
                ->method('getEntityManagerForClass')
                ->with(Language::class)
                ->willReturn($manager);
        } else {
            $this->doctrineHelper->expects($this->never())->method('getEntityManagerForClass');
        }

        $this->assertEquals($expected, $this->provider->updateTranslations($language));
    }

    public function hasTranslationsDataProvider()
    {
        yield 'positive' => ['return' => true, 'expected' => true];
        yield 'negative' => ['return' => false, 'expected' => false];
    }

    /**
     * @expectedException \Oro\Bundle\TranslationBundle\Exception\TranslationProviderException
     * @expectedExceptionMessage Unable to download translations for "test_code"
     */
    public function testDownloadTranslationsException()
    {
        $language = $this->createMock(Language::class);
        $language->expects($this->any())->method('getCode')->willReturn('test_code');

        $this->languageHelper->expects($this->once())
            ->method('isTranslationsAvailable')
            ->with($language)
            ->willReturn(true);

        $this->languageHelper->expects($this->once())
            ->method('downloadLanguageFile')
            ->with('test_code')
            ->willReturn(null);

        $this->provider->updateTranslations($language);
    }

    /**
     * @expectedException \Oro\Bundle\TranslationBundle\Exception\TranslationProviderException
     * @expectedExceptionMessage Unable to load translations for "test_code" from "test_path"
     */
    public function testLoadTranslationsException()
    {
        $language = $this->createMock(Language::class);
        $language->expects($this->any())->method('getCode')->willReturn('test_code');

        $this->languageHelper->expects($this->once())
            ->method('isTranslationsAvailable')
            ->with($language)
            ->willReturn(true);

        $this->languageHelper->expects($this->once())
            ->method('downloadLanguageFile')
            ->with('test_code')
            ->willReturn('test_path');

        $this->serviceProvider->expects($this->once())->method('loadTranslatesFromFile')->willReturn(null);

        $this->provider->updateTranslations($language);
    }
}
