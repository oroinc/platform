<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Symfony\Component\Translation\MessageCatalogue;

use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationLoader;
use Oro\Bundle\TranslationBundle\Translation\OrmTranslationResource;

class OrmTranslationLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var OrmTranslationLoader */
    protected $loader;

    protected function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(Translation::ENTITY_NAME)
            ->willReturn($this->em);

        $this->loader = new OrmTranslationLoader($doctrine);
    }

    public function testLoadWhenDatabaseDoesNotContainTranslationTable()
    {
        $locale        = 'fr';
        $domain        = 'test';
        $metadataCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();
        $resource      = new OrmTranslationResource($locale, $metadataCache);

        $translationTable = 'translation_table';

        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getTableName')
            ->willReturn($translationTable);
        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(Translation::ENTITY_NAME)
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
        $locale        = 'fr';
        $domain        = 'test';
        $metadataCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();
        $resource      = new OrmTranslationResource($locale, $metadataCache);

        $value1 = new Translation();
        $value1->setKey('label1');
        $value1->setScope(Translation::SCOPE_SYSTEM);
        $value1->setLocale($locale);
        $value1->setDomain($domain);
        $value1->setValue('value1 (SYSTEM_SCOPE)');

        $value2 = new Translation();
        $value2->setKey('label1');
        $value2->setScope(Translation::SCOPE_UI);
        $value2->setLocale($locale);
        $value2->setDomain($domain);
        $value2->setValue('value1 (UI_SCOPE)');

        $value3 = new Translation();
        $value3->setKey('label3');
        $value3->setScope(Translation::SCOPE_UI);
        $value3->setLocale($locale);
        $value3->setDomain($domain);
        $value3->setValue('value3');

        $values = [$value1, $value2, $value3];

        $translationTable = 'translation_table';

        $repo = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Entity\Repository\TranslationRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findValues')
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
            ->with(Translation::ENTITY_NAME)
            ->willReturn($metadata);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Translation::ENTITY_NAME)
            ->willReturn($repo);

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
