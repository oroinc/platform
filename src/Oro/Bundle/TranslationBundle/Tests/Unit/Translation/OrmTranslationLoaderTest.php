<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Symfony\Component\Translation\MessageCatalogue;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationLoader;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationResource;

class OrmTranslationLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var Connection|\PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var TranslationRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationManager;

    /** @var OrmTranslationLoader */
    protected $loader;

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(TranslationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

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

        $this->translationManager = $this
            ->getMockBuilder(TranslationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loader = new OrmTranslationLoader($doctrine, $this->translationManager);
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

        $translationTable = 'translation_table';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getTableName')
            ->willReturn($translationTable);
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(Translation::class)
            ->willReturn($metadata);

        $schemaManager = $this->getMockBuilder('Doctrine\DBAL\Schema\AbstractSchemaManager')
            ->disableOriginalConstructor()
            ->setMethods(['tablesExist'])
            ->getMockForAbstractClass();
        $this->connection->expects($this->once())
            ->method('connect');
        $this->connection->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager);
        $schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with($translationTable)
            ->willReturn(false);

        /** @var MessageCatalogue $result */
        $result = $this->loader->load($resource, $locale, $domain);

        $this->assertInstanceOf(
            'Symfony\Component\Translation\MessageCatalogue',
            $result
        );
        $this->assertEquals($locale, $result->getLocale());
        $this->assertEquals([], $result->getDomains());

        // test that 'checkDatabase' result was cached
        $this->loader->load($resource, $locale, $domain);
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

        $translationTable = 'translation_table';

        $this->repository->expects($this->once())
            ->method('findAllByLanguageAndDomain')
            ->with($locale, $domain)
            ->willReturn($values);

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getTableName')
            ->willReturn($translationTable);
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(Translation::class)
            ->willReturn($metadata);

        $schemaManager = $this->getMockBuilder('Doctrine\DBAL\Schema\AbstractSchemaManager')
            ->disableOriginalConstructor()
            ->setMethods(['tablesExist'])
            ->getMockForAbstractClass();
        $this->connection->expects($this->once())
            ->method('connect');
        $this->connection->expects($this->once())
            ->method('getSchemaManager')
            ->willReturn($schemaManager);
        $schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with($translationTable)
            ->willReturn(true);

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
