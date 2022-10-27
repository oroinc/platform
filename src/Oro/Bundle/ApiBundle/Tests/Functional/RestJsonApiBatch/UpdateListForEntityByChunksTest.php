<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;

/**
 * @dbIsolationPerTest
 */
class UpdateListForEntityByChunksTest extends RestJsonApiUpdateListTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/update_list_for_entity_by_chunks.yml']);
    }

    public function testUpdateEntitiesDelayedCreationOfChunkJobs(): void
    {
        // Decreasing batch size for several iterations
        $updateListCreateChunkJobsMessageProcessor = self::getContainer()
            ->get('oro_api.batch.async.update_list.create_chunk_jobs');
        $updateListCreateChunkJobsMessageProcessor->setBatchSize(2);

        $entityType = $this->getEntityType(TestDepartment::class);
        $this->processUpdateListDelayedCreationOfChunkJobs(
            TestDepartment::class,
            [
                'data' => $this->getRequestedData($entityType, 10),
            ]
        );

        $response = $this->cget(['entity' => $entityType]);
        $responseContent = $this->updateResponseContent(
            [
                'data' => $this->getExpectedResponseData($entityType, 10),
            ],
            $response
        );
        $this->assertResponseContains($responseContent, $response);
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
}
