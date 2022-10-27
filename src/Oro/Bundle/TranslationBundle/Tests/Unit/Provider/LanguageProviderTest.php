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
    /** @var LanguageRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var LanguageProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LanguageRepository::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Language::class)
            ->willReturn($this->repository);

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

        $this->repository->expects(self::any())
            ->method('getAvailableLanguageCodesAsArrayKeys')
            ->willReturnMap([
                [false, $allLanguages],
                [true, $enabledLanguages],
            ]);

        self::assertEqualsCanonicalizing(array_keys($allLanguages), $this->provider->getAvailableLanguageCodes(false));
        self::assertEqualsCanonicalizing(
            array_keys($enabledLanguages),
            $this->provider->getAvailableLanguageCodes(true)
        );
    }

    public function testGetAvailableLanguagesByCurrentUser(): void
    {
        $expectedLanguages = [new Language()];

        $this->repository->expects(self::once())
            ->method('getAvailableLanguagesByCurrentUser')
            ->with($this->aclHelper)
            ->willReturn($expectedLanguages);

        self::assertSame($expectedLanguages, $this->provider->getAvailableLanguagesByCurrentUser());
    }

    public function testGetLanguages(): void
    {
        $expectedLanguages = [new Language()];

        $this->repository->expects(self::once())
            ->method('getLanguages')
            ->willReturn($expectedLanguages);

        self::assertSame($expectedLanguages, $this->provider->getLanguages());
    }

    public function testGetDefaultLanguage(): void
    {
        $language = new Language();

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => Translator::DEFAULT_LOCALE])
            ->willReturn($language);

        self::assertSame($language, $this->provider->getDefaultLanguage());
    }
}
