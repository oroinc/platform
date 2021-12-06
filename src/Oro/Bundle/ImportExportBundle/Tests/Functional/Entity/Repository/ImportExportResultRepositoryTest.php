<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Entity\Repository\ImportExportResultRepository;
use Oro\Bundle\ImportExportBundle\Tests\Functional\DataFixtures\LoadImportExportResultData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ImportExportResultRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadImportExportResultData::class]);
    }

    private function getRepository(): ImportExportResultRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(ImportExportResult::class);
    }

    private function getManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(ImportExportResult::class);
    }

    public function testUpdateExpiredRecords()
    {
        /** @var ImportExportResult $notExpiredResult */
        $notExpiredResult = $this->getReference(LoadImportExportResultData::NOT_EXPIRED_IMPORT_EXPORT_RESULT);
        $this->assertFalse($notExpiredResult->isExpired());

        $from = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        $to = new \DateTime('tomorrow', new \DateTimeZone('UTC'));

        $this->getRepository()->updateExpiredRecords($from, $to);
        $this->getManager()->refresh($notExpiredResult);

        $this->assertTrue($notExpiredResult->isExpired());
    }
}
