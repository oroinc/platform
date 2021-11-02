<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationLoader;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationResource;
use Symfony\Component\Translation\MessageCatalogue;

class OrmTranslationLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var DatabaseChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $databaseChecker;

    /** @var OrmTranslationLoader */
    private $loader;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TranslationRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->with(Translation::class)
            ->willReturn($this->repository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Translation::class)
            ->willReturn($em);

        $this->databaseChecker = $this->createMock(DatabaseChecker::class);

        $this->loader = new OrmTranslationLoader($doctrine, $this->databaseChecker);
    }

    public function testLoadWhenDatabaseDoesNotContainTranslationTable()
    {
        $locale = 'fr';
        $domain = 'test';
        $metadataCache = $this->createMock(DynamicTranslationMetadataCache::class);
        $resource = new OrmTranslationResource($locale, $metadataCache);

        $result = $this->loader->load($resource, $locale, $domain);

        $this->assertInstanceOf(MessageCatalogue::class, $result);
        $this->assertEquals($locale, $result->getLocale());
        $this->assertEquals([], $result->getDomains());
    }

    public function testLoad()
    {
        $locale = 'fr';
        $domain = 'test';
        $metadataCache = $this->createMock(DynamicTranslationMetadataCache::class);
        $resource = new OrmTranslationResource($locale, $metadataCache);

        $values = [
            ['key' => 'label1', 'value' => 'value1 (SYSTEM_SCOPE)'],
            ['key' => 'label1', 'value' => 'value1 (UI_SCOPE)'],
            ['key' => 'label3', 'value' => 'value3'],
        ];

        $this->databaseChecker->expects(self::once())
            ->method('checkDatabase')
            ->willReturn(true);

        $this->repository->expects($this->once())
            ->method('findAllByLanguageAndDomain')
            ->with($locale, $domain)
            ->willReturn($values);

        $result = $this->loader->load($resource, $locale, $domain);

        $this->assertInstanceOf(MessageCatalogue::class, $result);
        $this->assertEquals($locale, $result->getLocale());
        $this->assertEquals('value1 (UI_SCOPE)', $result->get('label1', $domain));
        $this->assertEquals('value3', $result->get('label3', $domain));
    }
}
