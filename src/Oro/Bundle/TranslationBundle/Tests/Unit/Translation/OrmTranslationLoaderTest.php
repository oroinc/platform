<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrmTranslationLoaderTest extends TestCase
{
    private OrmTranslationLoader $loader;
    private Registry|MockObject $registry;
    private TranslationRepository|MockObject $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(Registry::class);
        $this->repository = $this->createMock(TranslationRepository::class);
        $this->loader = new OrmTranslationLoader($this->registry);
    }

    /** @dataProvider loadDataProvider */
    public function testLoad(
        array $translationsData,
        string $languageCode,
        ?string $domain,
        array $expectedResult
    ): void {
        $this->registry
            ->expects(self::once())
            ->method('getRepository')
            ->with(Translation::class)
            ->willReturn($this->repository);

        $this->repository
            ->expects(self::once())
            ->method('findDomainTranslations')
            ->willReturn($translationsData);

        $messageCatalogue = $this->loader->load('orm', $languageCode, $domain);

        if (!empty($translationsData)) {
            self::assertContains($domain, $messageCatalogue->getDomains());
        } else {
            self::assertEmpty($messageCatalogue->getDomains());
        }

        self::assertEquals($languageCode, $messageCatalogue->getLocale());
        self::assertEquals($expectedResult, $messageCatalogue->all($domain));
    }

    public function loadDataProvider(): array
    {
        return [
            'empty' => [
                'translationsData' => [],
                'languageCode' => 'en',
                'domain' => 'entities',
                'expectedResult' => [],
            ],
            'en' => [
                'translationsData' => [['key' => 'key.en', 'value' => 'value.en']],
                'languageCode' => 'en',
                'domain' => 'entities',
                'expectedResult' => ['key.en' => 'value.en'],
            ],
            'de_DE' => [
                'translationsData' => [['key' => 'key.de_DE', 'value' => 'value.de_DE']],
                'languageCode' => 'de_DE',
                'domain' => 'entities',
                'expectedResult' => ['key.de_DE' => 'value.de_DE'],
            ]
        ];
    }
}
