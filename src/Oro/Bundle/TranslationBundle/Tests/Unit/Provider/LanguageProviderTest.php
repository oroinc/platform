<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;

class LanguageProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|LanguageRepository */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject|LocaleSettings */
    protected $localeSettings;

    /** @var  \PHPUnit_Framework_MockObject_MockObject|AclHelper */
    protected $aclHelper;

    /** @var LanguageProvider */
    protected $provider;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(LanguageRepository::class)->disableOriginalConstructor()->getMock();
        $this->localeSettings = $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock();
        $this->aclHelper = $this->getMockBuilder(AclHelper::class)->disableOriginalConstructor()->getMock();

        $this->provider = new LanguageProvider($this->repository, $this->localeSettings, $this->aclHelper);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->repository, $this->localeSettings, $this->aclHelper);
    }

    public function testGetAvailableLanguages()
    {
        $this->repository->expects($this->once())
            ->method('getAvailableLanguageCodes')
            ->with(false)
            ->willReturn(['en', 'en_CA', 'fr_FR']);

        $this->localeSettings->expects($this->once())->method('getLanguage')->willReturn('en');

        $this->assertEquals(
            [
                'en' => 'English',
                'en_CA' => 'English (Canada)',
                'fr_FR' => 'French (France)'
            ],
            $this->provider->getAvailableLanguages()
        );
    }

    public function testGetEnabledLanguages()
    {
        $data = ['en', 'en_CA', 'fr_FR'];

        $this->repository->expects($this->once())
            ->method('getAvailableLanguageCodes')
            ->with(true)
            ->willReturn($data);

        $this->assertEquals($data, $this->provider->getEnabledLanguages());
    }

    public function testGetAvailableLanguagesByCurrentUser()
    {
        $data = [new Language()];

        $this->repository->expects($this->once())
            ->method('getAvailableLanguagesByCurrentUser')
            ->with($this->aclHelper)
            ->willReturn($data);

        $this->assertSame($this->provider->getAvailableLanguagesByCurrentUser(), $data);
    }
}
