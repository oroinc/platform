<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;

/**
 * @dbIsolationPerTest
 */
class UpdateListForEntityByChunksTest extends RestJsonApiUpdateListTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/update_list_for_entity_by_chunks.yml']);
    }

    private function getDepartmentId(string $title): int
    {
        /** @var TestDepartment|null $department */
        $department = $this->getEntityManager()->getRepository(TestDepartment::class)->findOneBy(['name' => $title]);
        if (null === $department) {
            throw new \RuntimeException(sprintf('The department "%s" not found.', $title));
        }

        return $department->getId();
    }

    public function testUpdateEntitiesDelayedCreationOfChunkJobs(): void
    {
        // Decreasing batch size for several iterations
        $updateListCreateChunkJobsMessageProcessor = self::getContainer()
            ->get('oro_api.batch.async.update_list.create_chunk_jobs');
        $updateListCreateChunkJobsMessageProcessor->setBatchSize(2);

        $entityType = $this->getEntityType(TestDepartment::class);
        $operationId = $this->processUpdateListDelayedCreationOfChunkJobs(
            TestDepartment::class,
            [
                'data' => $this->getRequestedData($entityType, 10),
            ]
        );

        $response = $this->cget(['entity' => $entityType], ['page[size]' => 10]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => $this->getExpectedResponseData($entityType, 10),
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);

        $operation = $this->getEntityManager()->find(AsyncOperation::class, $operationId);
        $summary = $operation->getSummary();
        unset($summary['aggregateTime']);
        self::assertSame(
            [
                'readCount'   => 10,
                'writeCount'  => 10,
                'errorCount'  => 0,
                'createCount' => 0,
                'updateCount' => 10
            ],
            $summary
        );
        self::assertSame($this->getAffectedEntities(10), $operation->getAffectedEntities());
    }

    private function getRequestedData(string $entityType, int $maxSize): array
    {
        $data = [];
        for ($i = 1; $i <= $maxSize; $i++) {
            $data[] = [
                'meta' => ['update' => true],
                'type' => $entityType,
                'id' => sprintf('<toString(@department%d->id)>', $i),
                'attributes' => ['title' => sprintf('Updated Department %d', $i)],
            ];
        }

        return $data;
    }

    private function getExpectedResponseData(string $entityType, int $maxSize): array
    {
        $data = [];
        for ($i = 1; $i <= $maxSize; $i++) {
            $data[] = [
                'type' => $entityType,
                'id' => sprintf('<toString(@department%d->id)>', $i),
                'attributes' => ['title' => sprintf('Updated Department %d', $i)],
            ];
        }

        return $data;
    }

    private function getAffectedEntities(int $maxSize): array
    {
        $affectedEntities = [];
        for ($i = 1; $i <= $maxSize; $i++) {
            $departmentId = $this->getDepartmentId(sprintf('Updated Department %d', $i));
            $affectedEntities['primary'][] = [$departmentId, (string)$departmentId, true];
        }

        return $affectedEntities;
    }
}
