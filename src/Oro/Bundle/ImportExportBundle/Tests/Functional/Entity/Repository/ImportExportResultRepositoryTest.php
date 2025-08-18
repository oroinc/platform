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
        /** @var ImportExportResult $expiredResult */
        $expiredResult = $this->getReference(LoadImportExportResultData::EXPIRED_IMPORT_EXPORT_RESULT);
        self::assertTrue($expiredResult->isExpired());

        /** @var ImportExportResult $notExpiredResult */
        $notExpiredResult = $this->getReference(LoadImportExportResultData::NOT_EXPIRED_IMPORT_EXPORT_RESULT);
        self::assertFalse($notExpiredResult->isExpired());

        $from = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        $to = new \DateTime('tomorrow', new \DateTimeZone('UTC'));

        $affectedCount = $this->getRepository()->updateExpiredRecordsAndReturnCount($from, $to);
        self::assertEquals(1, $affectedCount);

        $this->getManager()->refresh($notExpiredResult);

        self::assertTrue($notExpiredResult->isExpired());
    }
}
