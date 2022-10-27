<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ApiBundle\Batch\FileNameProvider;
use Oro\Bundle\ApiBundle\Command\CleanupAsyncOperationsCommand;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadOperationFilesData;
use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CleanupAsyncOperationsCommandTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadOperationFilesData::class]);
    }

    private function updateModificationDateForTestOperations(array $operationIds): void
    {
        $modificationDate = date_sub(
            new \DateTime('now', new \DateTimeZone('UTC')),
            new \DateInterval('P40D')
        );

        /** @var EntityRepository $repo */
        $repo = self::getContainer()->get('doctrine')->getRepository(AsyncOperation::class);
        $repo->createQueryBuilder('operation')
            ->update(AsyncOperation::class, 'operation')
            ->set('operation.updatedAt', ':updatedDate')
            ->where('operation.id in (:ids)')
            ->setParameter('ids', $operationIds)
            ->setParameter('updatedDate', $modificationDate)
            ->getQuery()
            ->execute();
    }

    private function updateElapsedTimeForTestOperations(array $operationIds, int $operationTimeout = 3600): void
    {
        /** @var EntityRepository $repo */
        $repo = self::getContainer()->get('doctrine')->getRepository(AsyncOperation::class);
        $repo->createQueryBuilder('operation')
            ->update(AsyncOperation::class, 'operation')
            ->set('operation.elapsedTime', ':operationTimeout')
            ->where('operation.id in (:ids)')
            ->setParameter('ids', $operationIds)
            ->setParameter('operationTimeout', $operationTimeout)
            ->getQuery()
            ->execute();
    }

    public function testProcessOperationsCleanupWithDryRunOption()
    {
        $outdatedOperationId = $this->getReference('user_operation1')->getId();
        $notOutdatedOperationId = $this->getReference('user_operation2')->getId();
        $processingTimeElapsedOperationId = $this->getReference('user_operation3')->getId();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(AsyncOperation::class);
        $em->clear();
        $this->updateModificationDateForTestOperations([$outdatedOperationId]);
        $this->updateElapsedTimeForTestOperations([$processingTimeElapsedOperationId]);

        self::runCommand(CleanupAsyncOperationsCommand::getDefaultName(), ['--dry-run']);

        /** @var FileManager $fileManager */
        $fileManager = self::getContainer()->get('oro_api.batch.file_manager');
        /** @var FileNameProvider $fileNameProvider */
        $fileNameProvider = self::getContainer()->get('oro_api.batch.file_name_provider');

        self::assertNotNull($em->find(AsyncOperation::class, $outdatedOperationId));
        self::assertTrue($fileManager->hasFile($fileNameProvider->getErrorIndexFileName($outdatedOperationId)));
        self::assertTrue($fileManager->hasFile($fileNameProvider->getInfoFileName($outdatedOperationId)));

        self::assertNotNull($em->find(AsyncOperation::class, $notOutdatedOperationId));
        self::assertTrue($fileManager->hasFile($fileNameProvider->getErrorIndexFileName($notOutdatedOperationId)));
        self::assertTrue($fileManager->hasFile($fileNameProvider->getInfoFileName($notOutdatedOperationId)));

        self::assertNotNull($em->find(AsyncOperation::class, $processingTimeElapsedOperationId));
        self::assertTrue(
            $fileManager->hasFile(
                $fileNameProvider->getErrorIndexFileName($processingTimeElapsedOperationId)
            )
        );
        self::assertTrue($fileManager->hasFile($fileNameProvider->getInfoFileName($processingTimeElapsedOperationId)));
    }

    public function testProcessOperationsCleanup()
    {
        $processingTimeElapsedOperationsIds = [
            $this->getReference('user_operation3')->getId(),
        ];
        $outdatedOperationIds = [
            $this->getReference('user_operation1')->getId(),
            $this->getReference('user_operation2')->getId(),
            $this->getReference('subordinate_bu_user_operation')->getId()
        ];
        $notOutdatedOperationIds = [
            $this->getReference('default_bu_user_operation')->getId(),
            $this->getReference('root_bu_user_operation')->getId()
        ];

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine')->getManagerForClass(AsyncOperation::class);
        $em->clear();
        $this->updateModificationDateForTestOperations($outdatedOperationIds);
        $this->updateElapsedTimeForTestOperations($processingTimeElapsedOperationsIds);

        self::runCommand(CleanupAsyncOperationsCommand::getDefaultName());

        /** @var FileManager $fileManager */
        $fileManager = self::getContainer()->get('oro_api.batch.file_manager');
        /** @var FileNameProvider $fileNameProvider */
        $fileNameProvider = self::getContainer()->get('oro_api.batch.file_name_provider');

        foreach ($outdatedOperationIds as $id) {
            self::assertTrue(null === $em->find(AsyncOperation::class, $id));
            self::assertFalse($fileManager->hasFile($fileNameProvider->getErrorIndexFileName($id)));
            self::assertFalse($fileManager->hasFile($fileNameProvider->getInfoFileName($id)));
        }

        foreach ($processingTimeElapsedOperationsIds as $id) {
            self::assertTrue(null === $em->find(AsyncOperation::class, $id));
            self::assertFalse($fileManager->hasFile($fileNameProvider->getErrorIndexFileName($id)));
            self::assertFalse($fileManager->hasFile($fileNameProvider->getInfoFileName($id)));
        }

        foreach ($notOutdatedOperationIds as $id) {
            self::assertNotNull($em->find(AsyncOperation::class, $id));
        }
    }
}
