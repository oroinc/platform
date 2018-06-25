<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Helper;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Helper\LanguageHelper;
use Oro\Bundle\TranslationBundle\Provider\OroTranslationAdapter;
use Oro\Bundle\TranslationBundle\Provider\PackagesProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LanguageHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationStatisticProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $statisticProvider;

    /** @var PackagesProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $packagesProvider;

    /** @var OroTranslationAdapter|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationAdapter;

    /** @var TranslationServiceProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $translationServiceProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var LanguageHelper */
    protected $helper;

    protected function setUp()
    {
        $this->statisticProvider = $this->getMockBuilder(TranslationStatisticProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->packagesProvider = $this->getMockBuilder(PackagesProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translationAdapter = $this->getMockBuilder(OroTranslationAdapter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translationServiceProvider = $this->getMockBuilder(TranslationServiceProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new LanguageHelper(
            $this->statisticProvider,
            $this->packagesProvider,
            $this->translationAdapter,
            $this->translationServiceProvider,
            $this->configManager
        );
    }

    protected function tearDown()
    {
        unset(
            $this->helper,
            $this->statisticProvider,
            $this->packagesProvider,
            $this->translationAdapter,
            $this->translationServiceProvider,
            $this->configManager
        );
    }

    public function testIsAvailableInstallTranslatesAndNoCode()
    {
        $this->statisticProvider->expects($this->never())->method('get');

        $this->assertFalse($this->helper->isAvailableInstallTranslates(new Language()));
    }

    public function testIsAvailableInstallTranslatesAndInstalledBuildDate()
    {
        $this->statisticProvider->expects($this->never())->method('get');

        $language = (new Language())->setInstalledBuildDate(new \DateTime());

        $this->assertFalse($this->helper->isAvailableInstallTranslates($language));
    }

    public function testIsAvailableInstallTranslatesAndUnknownCode()
    {
        $this->statisticProvider->expects($this->once())->method('get')->willReturn([]);

        $language = (new Language())->setCode('unknown');

        $this->assertFalse($this->helper->isAvailableInstallTranslates($language));
    }

    public function testIsAvailableInstallTranslates()
    {
        $this->statisticProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                ['code' => 'en']
            ]);

        $language = (new Language())->setCode('en');

        $this->assertTrue($this->helper->isAvailableInstallTranslates($language));
    }

    public function testIsTranslationsAvailablePositive()
    {
        $this->statisticProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                ['code' => 'en']
            ]);

        $language = (new Language())->setCode('en');

        $this->assertTrue($this->helper->isTranslationsAvailable($language));
    }

    public function testIsTranslationsAvailableNegative()
    {
        $this->assertFalse($this->helper->isTranslationsAvailable(new Language()));
    }

    public function testIsAvailableUpdateTranslatesAndNoCode()
    {
        $this->statisticProvider->expects($this->never())->method('get');

        $this->assertFalse($this->helper->isAvailableUpdateTranslates(new Language()));
    }

    public function testIsAvailableUpdateTranslatesAndNoInstalledBuildDate()
    {
        $this->statisticProvider->expects($this->never())->method('get');

        $language = (new Language())->setCode('en');

        $this->assertFalse($this->helper->isAvailableUpdateTranslates($language));
    }

    public function testIsAvailableUpdateTranslatesAndUnknownCode()
    {
        $this->statisticProvider->expects($this->once())->method('get')->willReturn([]);

        $language = (new Language())->setCode('unknown')->setInstalledBuildDate(new \DateTime());

        $this->assertFalse($this->helper->isAvailableUpdateTranslates($language));
    }

    public function testIsAvailableUpdateTranslatesAndNoUpdates()
    {
        $date = new \DateTime();

        $this->statisticProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                [
                    'code' => 'en',
                    'lastBuildDate' => $date->format('Y-m-d\TH:i:sO'),
                ]
            ]);

        $language = (new Language())->setCode('en')->setInstalledBuildDate($date);

        $this->assertFalse($this->helper->isAvailableUpdateTranslates($language));
    }

    public function testIsAvailableUpdateTranslatesAndHasUpdates()
    {
        $date = new \DateTime();

        $this->statisticProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                [
                    'code' => 'en',
                    'lastBuildDate' => $date->format('Y-m-d\TH:i:sO'),
                ]
            ]);

        $date->sub(new \DateInterval('P1D'));

        $language = (new Language())->setCode('en')->setInstalledBuildDate($date);

        $this->assertTrue($this->helper->isAvailableUpdateTranslates($language));
    }

    public function testGetTranslationStatusAndUnknownCode()
    {
        $this->statisticProvider->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $this->assertNull($this->helper->getTranslationStatus(new Language()));
    }

    public function testGetTranslationStatus()
    {
        $this->statisticProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                ['code' => 'en_US', 'translationStatus' => 50],
            ]);

        $language = (new Language())->setCode('en_US');

        $this->assertEquals(50, $this->helper->getTranslationStatus($language));
    }

    public function testGetLanguageStatistic()
    {
        $statistic = ['code' => 'en_US', 'translationStatus' => 50, 'lastBuildDate' => date('Y-m-dTH:i:sO')];
        $this->statisticProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                $statistic,
            ]);
        $expectedStatistic = $statistic;
        $expectedStatistic['lastBuildDate'] = new \DateTime($expectedStatistic['lastBuildDate']);
        $this->assertEquals($expectedStatistic, $this->helper->getLanguageStatistic('en_US'));
    }

    public function testDownloadLanguageFile()
    {
        $this->packagesProvider->expects($this->once())
            ->method('getInstalledPackages')
            ->willReturn(['Oro']);
        $this->translationServiceProvider->expects($this->once())
            ->method('setAdapter');
        $this->translationServiceProvider->expects($this->once())
            ->method('getTmpDir')
            ->with('download_en_US')
            ->willReturn('test_path');
        $this->translationServiceProvider->expects($this->once())
            ->method('download')
            ->with('test_path', ['Oro'], 'en_US')
            ->willReturn(true);
        $this->assertEquals('test_path', $this->helper->downloadLanguageFile('en_US'));
    }

    /**
     * @dataProvider isDefaultLanguageDataProvider
     *
     * @param Language $language
     * @param Language $defaultLanguage
     * @param bool $expected
     */
    public function testIsDefaultLanguage($language, $defaultLanguage, $expected)
    {
        $this->configManager
            ->expects($this->any())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::LANGUAGE))
            ->willReturn($defaultLanguage);

        $this->assertEquals($expected, $this->helper->isDefaultLanguage($language));
    }

    /**
     * return array
     */
    public function isDefaultLanguageDataProvider()
    {
        $lang = (new Language())->setCode('en');
        return [
            'true' => [
                'language' => $lang,
                'defaultLanguage' => 'en',
                'expected' => true
            ],
            'false' => [
                'language' => $lang,
                'defaultLanguage' => 'fr_FR',
                'expected' => false
            ],
        ];
    }
}
