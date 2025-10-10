<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\ImportExport;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Tests\Functional\AbstractImportExportTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedEntityRelatesToHidden;

class ImportExportEntitiesRelatesToHiddenTest extends AbstractImportExportTestCase
{
    public function testExport(): void
    {
        $this->assertExportWorks(
            $this->getExportImportConfiguration(),
            $this->getFullPathToDataFile('export.csv'),
        );
    }

    public function testImport(): void
    {
        $this->assertImportWorks(
            $this->getExportImportConfiguration(),
            $this->getFullPathToDataFile('import.csv')
        );

        self::assertCount(3, $this->getRepository()->findAll());
    }

    private function getFullPathToDataFile(string $fileName): string
    {
        $dataDir = $this->getContainer()
            ->get('kernel')
            ->locateResource('@OroEntityExtendBundle/Tests/Functional/DataFixtures/Data');

        return $dataDir . DIRECTORY_SEPARATOR . $fileName;
    }

    private function getRepository(): EntityRepository
    {
        return self::getContainer()
            ->get('doctrine')
            ->getRepository(TestExtendedEntityRelatesToHidden::class);
    }

    private function getExportImportConfiguration(): ImportExportConfiguration
    {
        return new ImportExportConfiguration([
            ImportExportConfiguration::FIELD_ENTITY_CLASS => TestExtendedEntityRelatesToHidden::class,
            ImportExportConfiguration::FIELD_EXPORT_PROCESSOR_ALIAS => 'test_extend_relates_hidden',
            ImportExportConfiguration::FIELD_EXPORT_TEMPLATE_PROCESSOR_ALIAS => 'test_extend_relates_hidden',
            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'test_extend_relates_hidden'
        ]);
    }
}
