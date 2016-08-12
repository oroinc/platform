<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Helper;

use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Helper\LanguageHelper;
use Oro\Bundle\TranslationBundle\Provider\TranslationStatisticProvider;

class LanguageHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationStatisticProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var LanguageHelper */
    protected $helper;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder(TranslationStatisticProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new LanguageHelper($this->provider);
    }

    protected function tearDown()
    {
        unset($this->helper, $this->provider);
    }

    public function testIsAvailableInstallTranslatesAndNoCode()
    {
        $this->provider->expects($this->never())->method('get');

        $this->assertFalse($this->helper->isAvailableInstallTranslates(new Language()));
    }

    public function testIsAvailableInstallTranslatesAndInstalledBuildDate()
    {
        $this->provider->expects($this->never())->method('get');

        $language = (new Language())->setInstalledBuildDate(new \DateTime());

        $this->assertFalse($this->helper->isAvailableInstallTranslates($language));
    }

    public function testIsAvailableInstallTranslatesAndUnknownCode()
    {
        $this->provider->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $language = (new Language())->setCode('unknown');

        $this->assertFalse($this->helper->isAvailableInstallTranslates($language));
    }

    public function testIsAvailableInstallTranslates()
    {
        $this->provider->expects($this->once())
            ->method('get')
            ->willReturn([
                ['code' => 'en']
            ]);

        $language = (new Language())->setCode('en');

        $this->assertTrue($this->helper->isAvailableInstallTranslates($language));
    }

    public function testIsAvailableUpdateTranslatesAndNoCode()
    {
        $this->provider->expects($this->never())->method('get');

        $this->assertFalse($this->helper->isAvailableUpdateTranslates(new Language()));
    }

    public function testIsAvailableUpdateTranslatesAndNoInstalledBuildDate()
    {
        $this->provider->expects($this->never())->method('get');

        $language = (new Language())->setCode('en');

        $this->assertFalse($this->helper->isAvailableUpdateTranslates($language));
    }

    public function testIsAvailableUpdateTranslatesAndUnknownCode()
    {
        $this->provider->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $language = (new Language())->setCode('unknown')->setInstalledBuildDate(new \DateTime());

        $this->assertFalse($this->helper->isAvailableUpdateTranslates($language));
    }

    public function testIsAvailableUpdateTranslatesAndNoUpdates()
    {
        $date = new \DateTime();

        $this->provider->expects($this->once())
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

        $this->provider->expects($this->once())
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
        $this->provider->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $this->assertNull($this->helper->getTranslationStatus(new Language()));
    }

    public function testGetTranslationStatusAndEnCode()
    {
        // TODO: should be removed in https://magecore.atlassian.net/browse/BAP-10608
        $this->provider->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $language = (new Language())->setCode('en');

        $this->assertEquals(100, $this->helper->getTranslationStatus($language));
    }

    public function testGetTranslationStatus()
    {
        $this->provider->expects($this->once())
            ->method('get')
            ->willReturn([
                ['code' => 'en_US', 'translationStatus' => 50],
            ]);

        $language = (new Language())->setCode('en_US');

        $this->assertEquals(50, $this->helper->getTranslationStatus($language));
    }
}
