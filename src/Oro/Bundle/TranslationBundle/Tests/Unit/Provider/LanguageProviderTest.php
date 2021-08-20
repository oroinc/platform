<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Repository\LanguageRepository;
use Oro\Bundle\TranslationBundle\Provider\LanguageProvider;
use Oro\Bundle\TranslationBundle\Translation\Translator;

class LanguageProviderTest extends \PHPUnit\Framework\TestCase
{
    private LanguageRepository $repository;
    private AclHelper $aclHelper;
    private LanguageProvider $provider;

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function setUp(): void
    {
        $this->repository = $this->createMock(LanguageRepository::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->method('getRepository')->willReturnMap([[Language::class, null, $this->repository]]);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->provider = new LanguageProvider(
            $doctrine,
            $this->createMock(LocaleSettings::class),
            $this->aclHelper
        );
    }

    public function testGetAvailableLanguageCodes(): void
    {
        $allLanguages = ['en' => true, 'de_DE' => true, 'uk_UA' => true];
        $enabledLanguages = ['en' => true, 'uk_UA' => true];

        $this->repository->method('getAvailableLanguageCodesAsArrayKeys')->willReturnMap([
            [false, $allLanguages],
            [true, $enabledLanguages],
        ]);

        static::assertSame(\array_keys($allLanguages), $this->provider->getAvailableLanguageCodes(false));
        static::assertSame(\array_keys($enabledLanguages), $this->provider->getAvailableLanguageCodes(true));
    }

    public function testGetAvailableLanguagesByCurrentUser(): void
    {
        $expectedLanguages = [new Language()];

        $this->repository->expects(static::once())
            ->method('getAvailableLanguagesByCurrentUser')
            ->with($this->aclHelper)
            ->willReturn($expectedLanguages);

        static::assertSame($expectedLanguages, $this->provider->getAvailableLanguagesByCurrentUser());
    }

    public function testGetLanguages(): void
    {
        $expectedLanguages = [new Language()];

        $this->repository->expects(static::once())->method('getLanguages')->willReturn($expectedLanguages);

        static::assertSame($expectedLanguages, $this->provider->getLanguages());
    }

    public function testGetDefaultLanguage(): void
    {
        $language = new Language();

        $this->repository->expects(static::once())
            ->method('findOneBy')
            ->with(['code' => Translator::DEFAULT_LOCALE])
            ->willReturn($language);

        static::assertSame($language, $this->provider->getDefaultLanguage());
    }
}
