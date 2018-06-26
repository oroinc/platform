<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Tools\DatabaseChecker;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationLoader;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationResource;
use Symfony\Component\Translation\MessageCatalogue;

class OrmTranslationLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var TranslationRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var DatabaseChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $databaseChecker;

    /** @var OrmTranslationLoader */
    protected $loader;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(TranslationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Translation::class)
            ->willReturn($this->em);

        $this->em->expects($this->any())
            ->method('getRepository')
            ->with(Translation::class)
            ->willReturn($this->repository);

        $this->databaseChecker = $this->getMockBuilder(DatabaseChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loader = new OrmTranslationLoader($doctrine, $this->databaseChecker);
    }

    public function testLoadWhenDatabaseDoesNotContainTranslationTable()
    {
        $locale = 'fr';
        $domain = 'test';
        $metadataCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();
        $resource = new OrmTranslationResource($locale, $metadataCache);

        /** @var MessageCatalogue $result */
        $result = $this->loader->load($resource, $locale, $domain);

        $this->assertInstanceOf(
            'Symfony\Component\Translation\MessageCatalogue',
            $result
        );
        $this->assertEquals($locale, $result->getLocale());
        $this->assertEquals([], $result->getDomains());
    }

    public function testLoad()
    {
        $locale = 'fr';
        $domain = 'test';
        $metadataCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();
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

        /** @var MessageCatalogue $result */
        $result = $this->loader->load($resource, $locale, $domain);

        $this->assertInstanceOf(
            'Symfony\Component\Translation\MessageCatalogue',
            $result
        );
        $this->assertEquals($locale, $result->getLocale());
        $this->assertEquals('value1 (UI_SCOPE)', $result->get('label1', $domain));
        $this->assertEquals('value3', $result->get('label3', $domain));
    }
}
