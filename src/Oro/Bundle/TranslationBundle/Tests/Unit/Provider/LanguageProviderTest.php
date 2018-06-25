<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class LanguageProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|LanguageRepository */
    protected $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    protected $managerRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|LocaleSettings */
    protected $localeSettings;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|AclHelper */
    protected $aclHelper;

    /** @var LanguageProvider */
    protected $provider;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(LanguageRepository::class)->disableOriginalConstructor()->getMock();
        $this->managerRegistry = $this->getMockBuilder(ManagerRegistry::class)->disableOriginalConstructor()->getMock();
        $this->managerRegistry->expects($this->once())->method('getRepository')->willReturn($this->repository);

        $this->localeSettings = $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock();
        $this->aclHelper = $this->getMockBuilder(AclHelper::class)->disableOriginalConstructor()->getMock();

        $this->provider = new LanguageProvider($this->managerRegistry, $this->localeSettings, $this->aclHelper);
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

    public function testGetLanguages()
    {
        $data = [new Language()];

        $this->repository->expects($this->once())->method('getLanguages')->willReturn($data);

        $this->assertSame($data, $this->provider->getLanguages());
    }

    public function testGetDefaultLanguage()
    {
        $language = new Language();

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['code' => Translator::DEFAULT_LOCALE])
            ->willReturn($language);

        $this->assertSame($language, $this->provider->getDefaultLanguage());
    }
}
