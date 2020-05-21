<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\Entity\Repository\ImportExportResultRepository;
use Oro\Bundle\ImportExportBundle\Tests\Functional\DataFixtures\LoadImportExportResultData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ImportExportResultRepositoryTest extends WebTestCase
{
    /**
     * @var ImportExportResultRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([
            LoadImportExportResultData::class
        ]);

        $this->repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass(ImportExportResult::class)
            ->getRepository(ImportExportResult::class);
    }

    public function testUpdateExpiredRecords()
    {
        /** @var ImportExportResult $notExpiredResult */
        $notExpiredResult = $this->getReference(LoadImportExportResultData::NOT_EXPIRED_IMPORT_EXPORT_RESULT);
        $this->assertFalse($notExpiredResult->isExpired());

        $from = new \DateTime('yesterday', new \DateTimeZone('UTC'));
        $to = new \DateTime('tomorrow', new \DateTimeZone('UTC'));

        $this->repository->updateExpiredRecords($from, $to);
        $this->getManager()->refresh($notExpiredResult);

        $this->assertTrue($notExpiredResult->isExpired());
    }

    /**
     * @return EntityManager
     */
    private function getManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(ImportExportResult::class);
    }
}
