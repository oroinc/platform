<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListProcessChunkTopic;
use Oro\Bundle\ApiBundle\Batch\Async\Topic\UpdateListTopic;
use Oro\Bundle\ApiBundle\Batch\Model\BatchError;
use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor\BatchUpdateExceptionController;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRuntimeException;
use Oro\Component\MessageQueue\Job\Job;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class UpdateListTest extends RestJsonApiUpdateListTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadOrganization::class]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->getBatchUpdateExceptionController()->clear();
        parent::tearDown();
    }

    /**
     * @param string[] $names
     *
     * @return TestDepartment[]
     */
    private function createDepartments(array $names): array
    {
        $em = $this->getEntityManager();
        $entities = [];
        foreach ($names as $name) {
            $entity = new TestDepartment();
            $entity->setName($name);
            $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $em->persist($entity);
            $entities[] = $entity;
        }
        $em->flush();
        $em->clear();

        return $entities;
    }

    /**
     * @param TestDepartment[] $entities
     *
     * @return string[]
     */
    private function getDepartmentNames(array $entities): array
    {
        $names = array_map(
            function (TestDepartment $entity) {
                return $entity->getName();
            },
            $entities
        );
        sort($names);

        return $names;
    }

    private function getAsyncOperationCount(): int
    {
        return $this->getEntityManager(AsyncOperation::class)
            ->createQueryBuilder()
            ->select('COUNT(o.id) AS count')
            ->from(AsyncOperation::class, 'o')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createBatchError(
        string $id,
        int $statusCode,
        string $title,
        ?string $detail = null,
        ?int $itemIndex = null,
        ?string $sourcePointer = null
    ): BatchError {
        $error = BatchError::create($title, $detail)
            ->setId($id)
            ->setStatusCode($statusCode);
        if (null !== $itemIndex) {
            $error->setItemIndex($itemIndex);
        }
        if (null !== $sourcePointer) {
            $error->setSource(ErrorSource::createByPointer($sourcePointer));
        }

        return $error;
    }

    private function getBatchUpdateExceptionController(): BatchUpdateExceptionController
    {
        return self::getContainer()->get('oro_api.tests.batch_update_exception_controller');
    }

    public function testTryToSendUpdateListRequestWhenGetActionIsDisabled(): void
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['get' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cpatch(
            ['entity' => $entityType],
            ['data' => [['type' => $entityType, 'attributes' => ['title' => 'New Department 1']]]],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, DELETE');
    }

    public function testTryToSendUpdateListRequestWhenBothCreateAndUpdateActionsAreDisabled(): void
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            ['actions' => ['create' => false, 'update' => false]],
            true
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cpatch(
            ['entity' => $entityType],
            ['data' => [['type' => $entityType, 'attributes' => ['title' => 'New Department 1']]]],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, DELETE');
    }

    public function testTryToSendUpdateListRequestWithoutCreatePermissionForAsyncOperationEntity(): void
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::BASIC_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL
            ]
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cpatch(
            ['entity' => $entityType],
            ['data' => [['type' => $entityType, 'attributes' => ['title' => 'New Department 1']]]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToSendUpdateListRequestWithoutViewPermissionForAsyncOperationEntity(): void
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::BASIC_LEVEL
            ]
        );

        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->cpatch(
            ['entity' => $entityType],
            ['data' => [['type' => $entityType, 'attributes' => ['title' => 'New Department 1']]]],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToProcessWhenLoadOfChunkDataFailed(): void
    {
        $entityClass = TestDepartment::class;
        $data = [
            'data' => [
                [
                    'type' => $this->getEntityType($entityClass),
                    'attributes' => ['title' => 'New Department 1']
                ]
            ]
        ];
        $expectedJobs = [
            [
                'result'  => MessageProcessorInterface::REJECT,
                'status'  => Job::STATUS_FAILED,
                'summary' => [
                    'readCount'   => 0,
                    'writeCount'  => 0,
                    'errorCount'  => 1,
                    'createCount' => 0,
                    'updateCount' => 0
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs, 'initialize');
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_FAILED,
            1.0,
            true,
            [
                'readCount'   => 0,
                'writeCount'  => 0,
                'errorCount'  => 1,
                'createCount' => 0,
                'updateCount' => 0
            ]
        );

        $this->getEntityManager()->clear();
        self::assertCount(0, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 500,
                'title'  => 'runtime exception',
                'detail' => 'A test exception from the "initialize" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWithoutMessageQueueWhenLoadOfChunkDataFailed(): void
    {
        $entityClass = TestDepartment::class;
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            'initialize'
        );

        $this->getEntityManager()->clear();
        self::assertCount(0, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "initialize" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWithoutMessageQueueAndWithSyncModeWhenLoadOfChunkDataFailed(): void
    {
        $entityClass = TestDepartment::class;
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            false,
            'initialize'
        );
        $this->assertResponseValidationError(
            [
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "initialize" group.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        $this->getEntityManager()->clear();
        self::assertCount(0, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());

        $operationId = $this->getLastOperationId();
        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "initialize" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWhenFinalizationOfChunkFailed(): void
    {
        $entityClass = TestDepartment::class;
        $data = [
            'data' => [
                [
                    'type' => $this->getEntityType($entityClass),
                    'attributes' => ['title' => 'New Department 1']
                ]
            ]
        ];
        $expectedJobs = [
            [
                'result'  => MessageProcessorInterface::REJECT,
                'status'  => Job::STATUS_FAILED,
                'summary' => [
                    'readCount'   => 1,
                    'writeCount'  => 1,
                    'errorCount'  => 1,
                    'createCount' => 1,
                    'updateCount' => 0
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs, 'finalize');
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_FAILED,
            1.0,
            true,
            [
                'readCount'   => 1,
                'writeCount'  => 1,
                'errorCount'  => 1,
                'createCount' => 1,
                'updateCount' => 0
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['New Department 1'], $this->getDepartmentNames($entities));

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 500,
                'title'  => 'runtime exception',
                'detail' => 'A test exception from the "finalize" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWithoutMessageQueueWhenFinalizationOfChunkFailed(): void
    {
        $entityClass = TestDepartment::class;
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            'finalize'
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['New Department 1'], $this->getDepartmentNames($entities));

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "finalize" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWithoutMessageQueueAndWithSyncModeWhenFinalizationOfChunkFailed(): void
    {
        $entityClass = TestDepartment::class;
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            false,
            'finalize'
        );
        $this->assertResponseValidationError(
            [
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "finalize" group.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['New Department 1'], $this->getDepartmentNames($entities));

        $operationId = $this->getLastOperationId();
        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "finalize" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWhenExceptionOccurredBeforeFlushChunkData(): void
    {
        $entityClass = TestDepartment::class;
        $data = [
            'data' => [
                [
                    'type' => $this->getEntityType($entityClass),
                    'attributes' => ['title' => 'New Department 1']
                ]
            ]
        ];
        $expectedJobs = [
            [
                'result'  => MessageProcessorInterface::REJECT,
                'status'  => Job::STATUS_FAILED,
                'summary' => [
                    'readCount'   => 1,
                    'writeCount'  => 0,
                    'errorCount'  => 1,
                    'createCount' => 0,
                    'updateCount' => 0
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs(
            $entityClass,
            $data,
            $expectedJobs,
            'save_data:before_flush'
        );
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_FAILED,
            1.0,
            true,
            [
                'readCount'   => 1,
                'writeCount'  => 0,
                'errorCount'  => 1,
                'createCount' => 0,
                'updateCount' => 0
            ]
        );

        $this->getEntityManager()->clear();
        self::assertCount(0, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 500,
                'title'  => 'runtime exception',
                'detail' => 'A test exception from the "before_flush" stage of the "save_data" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWithoutMessageQueueWhenExceptionOccurredBeforeFlushChunkData(): void
    {
        $entityClass = TestDepartment::class;
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            'save_data:before_flush'
        );

        $this->getEntityManager()->clear();
        self::assertCount(0, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "before_flush" stage of the "save_data" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWithoutMessageQueueAndWithSyncModeWhenExceptionOccurredBeforeFlushChunkData(): void
    {
        $entityClass = TestDepartment::class;
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            false,
            'save_data:before_flush'
        );
        $this->assertResponseValidationError(
            [
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "before_flush" stage of the "save_data" group.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        $this->getEntityManager()->clear();
        self::assertCount(0, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());

        $operationId = $this->getLastOperationId();
        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "before_flush" stage of the "save_data" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWhenExceptionOccurredAfterFlushChunkData(): void
    {
        $entityClass = TestDepartment::class;
        $data = [
            'data' => [
                [
                    'type' => $this->getEntityType($entityClass),
                    'attributes' => ['title' => 'New Department 1']
                ]
            ]
        ];
        $expectedJobs = [
            [
                'result'  => MessageProcessorInterface::REJECT,
                'status'  => Job::STATUS_FAILED,
                'summary' => [
                    'readCount'   => 1,
                    'writeCount'  => 1,
                    'errorCount'  => 1,
                    'createCount' => 1,
                    'updateCount' => 0
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs, 'save_data');
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_FAILED,
            1.0,
            true,
            [
                'readCount'   => 1,
                'writeCount'  => 1,
                'errorCount'  => 1,
                'createCount' => 1,
                'updateCount' => 0
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['New Department 1'], $this->getDepartmentNames($entities));

        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-1-1',
                'status' => 500,
                'title'  => 'runtime exception',
                'detail' => 'A test exception from the "save_data" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWithoutMessageQueueWhenExceptionOccurredAfterFlushChunkData(): void
    {
        $entityClass = TestDepartment::class;
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            'save_data'
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['New Department 1'], $this->getDepartmentNames($entities));

        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "save_data" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWithoutMessageQueueAndWithSyncModeWhenExceptionOccurredAfterFlushChunkData(): void
    {
        $entityClass = TestDepartment::class;
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            false,
            'save_data'
        );
        $this->assertResponseValidationError(
            [
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "save_data" group.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['New Department 1'], $this->getDepartmentNames($entities));

        $operationId = $this->getLastOperationId();
        $this->assertAsyncOperationError(
            [
                'id' => $operationId . '-1-1',
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "save_data" group.',
                'source' => null
            ],
            $operationId
        );
    }

    public function testTryToProcessWhenExceptionOccurredBeforeSaveChunkErrors(): void
    {
        $entityClass = TestDepartment::class;
        $data = [
            'data' => [
                [
                    'type' => $this->getEntityType($entityClass),
                    'attributes' => ['title' => 'New Department 1']
                ]
            ]
        ];
        $expectedJobs = [
            [
                'result'  => MessageProcessorInterface::REJECT,
                'status'  => Job::STATUS_FAILED,
                'summary' => [
                    'readCount'   => 1,
                    'writeCount'  => 1,
                    'errorCount'  => 0,
                    'createCount' => 1,
                    'updateCount' => 0
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs, 'save_errors');
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_FAILED,
            1.0,
            true,
            [
                'readCount'   => 1,
                'writeCount'  => 1,
                'errorCount'  => 0,
                'createCount' => 1,
                'updateCount' => 0
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['New Department 1'], $this->getDepartmentNames($entities));

        $this->assertAsyncOperationErrors([], $operationId);
    }

    public function testTryToProcessWithoutMessageQueueWhenExceptionOccurredBeforeSaveChunkErrors(): void
    {
        $entityClass = TestDepartment::class;
        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            'save_errors'
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['New Department 1'], $this->getDepartmentNames($entities));

        $this->assertAsyncOperationErrors([], $operationId);
    }

    public function testTryToProcessWithoutMessageQueueAndWithSyncModeWhenExceptionOccurredBeforeSaveChunkErrors(): void
    {
        $entityClass = TestDepartment::class;
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            false,
            'save_errors'
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_ACCEPTED);

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['New Department 1'], $this->getDepartmentNames($entities));

        $this->assertAsyncOperationErrors([], $this->getLastOperationId());
    }

    public function testTryToProcessWhenExceptionOccurredInNormalizeResult(): void
    {
        $entityClass = TestDepartment::class;
        $data = [
            'data' => [
                [
                    'type' => $this->getEntityType($entityClass),
                    'attributes' => ['title' => 'New Department 1']
                ]
            ]
        ];
        $operationId = $this->sendUpdateListRequest($entityClass, $data);
        try {
            $this->consumeMessages();
            $this->getBatchUpdateExceptionController()->setFailedGroups(['normalize_result']);
            $this->consumeAllMessages();
            self::fail(sprintf('Expected %s', JobRuntimeException::class));
        } catch (JobRuntimeException $e) {
            // it is expected exception
        }

        $this->getEntityManager()->clear();
        self::assertCount(0, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());

        $this->assertAsyncOperationErrors([], $operationId);
    }

    public function testTryToProcessWithoutMessageQueueWhenExceptionOccurredInNormalizeResult(): void
    {
        $entityClass = TestDepartment::class;
        $this->disableProcessByMessageQueue();
        $this->getBatchUpdateExceptionController()->setFailedGroups(['normalize_result']);
        $response = $this->cpatch(
            ['entity' => $this->getEntityType($entityClass)],
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "normalize_result" group.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        $this->getEntityManager()->clear();
        self::assertCount(0, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());

        $this->assertAsyncOperationErrors([], $this->getLastOperationId());
    }

    public function testTryToProcessWithoutMessageQueueAndWithSyncModeWhenExceptionOccurredInNormalizeResult(): void
    {
        $entityClass = TestDepartment::class;
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            $entityClass,
            [
                'data' => [
                    [
                        'type' => $this->getEntityType($entityClass),
                        'attributes' => ['title' => 'New Department 1']
                    ]
                ]
            ],
            false,
            'normalize_result'
        );
        $this->assertResponseValidationError(
            [
                'title' => 'runtime exception',
                'detail' => 'A test exception from the "normalize_result" group.'
            ],
            $response,
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        $this->getEntityManager()->clear();
        self::assertCount(0, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());

        $this->assertAsyncOperationErrors([], $this->getLastOperationId());
    }

    public function testUpdateListRequest(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $data = [
            'data' => [
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 1']
                ],
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 2']
                ]
            ]
        ];
        $response = $this->cpatch(['entity' => $entityType], $data);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_ACCEPTED);
        self::assertSame(1, $this->getAsyncOperationCount());

        // check Content-Location header
        $operationId = $this->extractOperationIdFromContentLocationHeader($response);
        $locationUrl = $this->getUrl(
            $this->getItemRouteName(),
            ['entity' => $this->getEntityType(AsyncOperation::class), 'id' => $operationId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        self::assertEquals($locationUrl, $response->headers->get('Content-Location'));

        // check that an asynchronous operation is created
        /** @var AsyncOperation $operation */
        $operation = $this->getEntityManager()->getRepository(AsyncOperation::class)->find($operationId);
        self::assertNotNull($operation);
        self::assertEquals(AsyncOperation::STATUS_NEW, $operation->getStatus(), 'Status');
        self::assertNull($operation->getProgress(), 'Progress');
        self::assertSame([], $operation->getSummary(), 'Summary');
        self::assertEquals(ApiAction::UPDATE_LIST, $operation->getActionName(), 'ActionName');
        self::assertEquals(TestDepartment::class, $operation->getEntityClass(), 'EntityClass');
        self::assertNotNull($operation->getCreatedAt(), 'CreatedAt');
        self::assertNotNull($operation->getUpdatedAt(), 'UpdatedAt');
        self::assertNotEmpty($operation->getDataFileName(), 'DataFileName');
        self::assertNull($operation->getJobId(), 'JobId');
        self::assertNotNull($operation->getOrganization(), 'Organization');
        self::assertNotNull($operation->getOwner(), 'Owner');

        // check response content
        $newOperationAttributes = [
            'status'    => AsyncOperation::STATUS_NEW,
            'progress'  => null,
            'summary'   => null,
            'createdAt' => $operation->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $operation->getUpdatedAt()->format('Y-m-d\TH:i:s\Z')
        ];
        $this->assertAsyncOperationResponse($operationId, $newOperationAttributes, $response);

        // check that data file is stored
        $dataFileContent = $this->getSourceDataFileManager()->getFileContent($operation->getDataFileName());
        self::assertNotNull($dataFileContent, 'data file does not exist');
        self::assertEquals($data, self::jsonToArray($dataFileContent), 'data file content');

        // check that the message was sent to MQ
        self::assertMessageSent(
            UpdateListTopic::getName(),
            [
                'operationId'           => $operationId,
                'entityClass'           => $operation->getEntityClass(),
                'requestType'           => $this->getRequestType()->toArray(),
                'version'               => Version::LATEST,
                'synchronousMode'       => false,
                'fileName'              => $operation->getDataFileName(),
                'chunkSize'             => $this->getChunkSizeProvider()
                    ->getChunkSize(TestDepartment::class),
                'includedDataChunkSize' => $this->getChunkSizeProvider()
                    ->getIncludedDataChunkSize(TestDepartment::class)
            ]
        );
        self::clearMessageCollector();

        // check that the created asynchronous operation can be requested via REST API
        $this->assertAsyncOperationStatus($operationId, $newOperationAttributes);

        // check that sent "oro.api.update_list" message can be processed
        $this->consumeMessages(1);

        // check that data file was removed
        self::assertFalse($this->getSourceDataFileManager()->hasFile($operation->getDataFileName()));

        // check that the asynchronous operation was updated
        $this->assertAsyncOperationStatus(
            $operationId,
            [
                'status'   => AsyncOperation::STATUS_RUNNING,
                'progress' => 0.5,
                'summary'  => null
            ]
        );
        $this->assertAsyncOperationErrors([], $operationId);
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testUpdateListRequestWithInvalidData(string $data, string $error): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);
        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_list', ['entity' => $entityType]),
            [],
            [],
            $data
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_ACCEPTED);
        self::assertSame(1, $this->getAsyncOperationCount());

        // check Content-Location header
        $operationId = $this->extractOperationIdFromContentLocationHeader($response);
        $locationUrl = $this->getUrl(
            $this->getItemRouteName(),
            ['entity' => $this->getEntityType(AsyncOperation::class), 'id' => $operationId],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
        self::assertEquals($locationUrl, $response->headers->get('Content-Location'));

        // check that an asynchronous operation is created
        /** @var AsyncOperation $operation */
        $operation = $this->getEntityManager()->getRepository(AsyncOperation::class)->find($operationId);
        self::assertNotNull($operation);

        // check response content
        $newOperationAttributes = [
            'status'    => AsyncOperation::STATUS_NEW,
            'progress'  => null,
            'summary'   => null,
            'createdAt' => $operation->getCreatedAt()->format('Y-m-d\TH:i:s\Z'),
            'updatedAt' => $operation->getUpdatedAt()->format('Y-m-d\TH:i:s\Z')
        ];
        $this->assertAsyncOperationResponse($operationId, $newOperationAttributes, $response);

        // check that data file is stored
        $dataFileContent = $this->getSourceDataFileManager()->getFileContent($operation->getDataFileName());
        self::assertNotNull($dataFileContent, 'data file does not exist');
        self::assertEquals($data, $dataFileContent, 'data file content');

        // check that the message was sent to MQ
        self::assertMessageSent(
            UpdateListTopic::getName(),
            [
                'operationId'           => $operationId,
                'entityClass'           => $operation->getEntityClass(),
                'requestType'           => $this->getRequestType()->toArray(),
                'version'               => Version::LATEST,
                'synchronousMode'       => false,
                'fileName'              => $operation->getDataFileName(),
                'chunkSize'             => $this->getChunkSizeProvider()
                    ->getChunkSize(TestDepartment::class),
                'includedDataChunkSize' => $this->getChunkSizeProvider()
                    ->getIncludedDataChunkSize(TestDepartment::class)
            ]
        );

        self::clearMessageCollector();

        // check that the created asynchronous operation can be requested via REST API
        $this->assertAsyncOperationStatus($operationId, $newOperationAttributes);

        // check that sent "oro.api.update_list" message can be processed
        $this->consumeMessages(1);

        // check that data file was removed
        self::assertFalse($this->getSourceDataFileManager()->hasFile($operation->getDataFileName()));

        // check that the asynchronous operation was updated
        $this->assertAsyncOperationStatus(
            $operationId,
            [
                'status'   => AsyncOperation::STATUS_FAILED,
                'progress' => null,
                'summary'  => [
                    'readCount'   => 0,
                    'writeCount'  => 0,
                    'errorCount'  => 1,
                    'createCount' => 0,
                    'updateCount' => 0
                ]
            ]
        );
        $this->assertAsyncOperationError(
            [
                'id'     => $operationId . '-0-1',
                'title'  => 'async operation exception',
                'detail' => $error
            ],
            $operationId
        );
    }

    public function invalidDataProvider(): array
    {
        return [
            'invalid data'                              => [
                'data'  => '{"data": [{test}]}',
                'error' => 'Failed to parse the data file.'
                    . ' Parsing error in [1:12]. Start of string expected for object key. Instead got: t'
            ],
            'invalid data with invalid utf-8 character' => [
                'data'  => '{"data": ▿{"type": test"}}',
                'error' => 'Failed to parse the data file.'
                    . ' Parsing error in [1:10]. Unexpected character for value: ?'
            ]
        ];
    }

    public function testUpdateListRequestWithEmptyData(): void
    {
        $entityType = $this->getEntityType(TestDepartment::class);

        $response = $this->cpatch(['entity' => $entityType], [], [], false);
        self::assertSame(0, $this->getAsyncOperationCount());

        $this->assertResponseValidationError(
            [
                'title'  => 'request data constraint',
                'detail' => 'The request data should not be empty.'
            ],
            $response
        );
        self::assertFalse($response->headers->has('Content-Location'));

        self::assertEmptyMessages(UpdateListTopic::getName());
    }

    public function testSplitDataFileContainsOnlyOnePart(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $data = [
            'data' => [
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 1']
                ]
            ]
        ];

        $operationID = $this->sendUpdateListRequest($entityClass, $data);

        $this->consumeMessages();

        self::assertMessagesCount(UpdateListProcessChunkTopic::getName(), 1);

        $sentMessages = array_column(self::getSentMessages(), 'message');

        self::assertArrayContains(
            [
                [
                    'operationId'       => $operationID,
                    'entityClass'       => $entityClass,
                    'requestType'       => $this->getRequestType()->toArray(),
                    'version'           => Version::LATEST,
                    'synchronousMode'   => false,
                    'fileIndex'         => 0,
                    'firstRecordOffset' => 0,
                    'sectionName'       => 'data'
                ]
            ],
            $sentMessages
        );

        $part1Content = $this->getFileContentAndDeleteFile($sentMessages[0]['fileName']);
        self::assertEquals($data, self::jsonToArray($part1Content));

        self::assertIsInt($sentMessages[0]['jobId']);

        $job = $this->getJob($sentMessages[0]['jobId']);
        self::assertEquals(Job::STATUS_NEW, $job->getStatus());
        self::assertEquals(Job::STATUS_RUNNING, $job->getRootJob()->getStatus());
        self::assertEquals(0.5, $job->getRootJob()->getJobProgress());
    }

    public function testSplitDataFileContainsSeveralParts(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $chunkSize = $this->getChunkSizeProvider()->getChunkSize($entityClass);
        $data = ['data' => []];
        for ($i = 1; $i <= $chunkSize + 1; $i++) {
            $data['data'][] = [
                'type'       => $entityType,
                'attributes' => ['title' => sprintf('New Department %d', $i)]
            ];
        }
        $operationID = $this->sendUpdateListRequest($entityClass, $data);

        $this->consumeMessages();

        self::assertMessagesCount(UpdateListProcessChunkTopic::getName(), 2);

        $sentMessages = self::getSentMessagesByTopic(UpdateListProcessChunkTopic::getName());

        self::assertArrayContains(
            [
                [
                    'operationId'       => $operationID,
                    'entityClass'       => $entityClass,
                    'requestType'       => $this->getRequestType()->toArray(),
                    'version'           => Version::LATEST,
                    'synchronousMode'   => false,
                    'fileIndex'         => 0,
                    'firstRecordOffset' => 0,
                    'sectionName'       => 'data'
                ],
                [
                    'operationId'       => $operationID,
                    'entityClass'       => $entityClass,
                    'requestType'       => $this->getRequestType()->toArray(),
                    'version'           => Version::LATEST,
                    'synchronousMode'   => false,
                    'fileIndex'         => 1,
                    'firstRecordOffset' => $chunkSize,
                    'sectionName'       => 'data'
                ]
            ],
            $sentMessages
        );

        $part1ExpectedContent = $data;
        unset($part1ExpectedContent['data'][$chunkSize]);
        $part1Content = $this->getFileContentAndDeleteFile($sentMessages[0]['fileName']);
        self::assertEquals($part1ExpectedContent, self::jsonToArray($part1Content));
        $part2ExpectedContent = ['data' => [$data['data'][$chunkSize]]];
        unset($part2ExpectedContent['data'][$chunkSize]);
        $part2Content = $this->getFileContentAndDeleteFile($sentMessages[1]['fileName']);
        self::assertEquals($part2ExpectedContent, self::jsonToArray($part2Content));

        self::assertIsInt($sentMessages[0]['jobId']);
        self::assertIsInt($sentMessages[1]['jobId']);
        self::assertTrue($sentMessages[0]['jobId'] < $sentMessages[1]['jobId']);

        $job1 = $this->getJob($sentMessages[0]['jobId']);
        $job2 = $this->getJob($sentMessages[1]['jobId']);
        self::assertEquals(Job::STATUS_NEW, $job1->getStatus());
        self::assertEquals(Job::STATUS_NEW, $job2->getStatus());
        self::assertEquals(Job::STATUS_RUNNING, $job1->getRootJob()->getStatus());
        self::assertEquals(0.3333, $job1->getRootJob()->getJobProgress());
    }

    public function testProcessChunkForCreateEntitiesWhenNoErrors(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $data = [
            'data' => [
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 1']
                ],
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 2']
                ]
            ]
        ];
        $expectedJobs = [
            [
                'summary' => [
                    'readCount'   => 2,
                    'writeCount'  => 2,
                    'errorCount'  => 0,
                    'createCount' => 2,
                    'updateCount' => 0
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs);
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            false,
            [
                'readCount'   => 2,
                'writeCount'  => 2,
                'errorCount'  => 0,
                'createCount' => 2,
                'updateCount' => 0
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(
            ['New Department 1', 'New Department 2'],
            $this->getDepartmentNames($entities)
        );
    }

    public function testProcessChunkForUpdateEntitiesWhenNoErrors(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $departments = $this->createDepartments(['Department 1', 'Department 2']);
        $data = [];
        foreach ($departments as $department) {
            $data['data'][] = [
                'meta'       => ['update' => true],
                'type'       => $entityType,
                'id'         => (string)$department->getId(),
                'attributes' => ['title' => 'Updated ' . $department->getName()]
            ];
        }
        $expectedJobs = [
            [
                'summary' => [
                    'readCount'   => 2,
                    'writeCount'  => 2,
                    'errorCount'  => 0,
                    'createCount' => 0,
                    'updateCount' => 2
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs);
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            false,
            [
                'readCount'   => 2,
                'writeCount'  => 2,
                'errorCount'  => 0,
                'createCount' => 0,
                'updateCount' => 2
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(
            ['Updated Department 1', 'Updated Department 2'],
            $this->getDepartmentNames($entities)
        );
    }

    public function testProcessChunkForCreateEntitiesWhenNoErrorsAndSeveralChunks(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $chunkSize = $this->getChunkSizeProvider()->getChunkSize($entityClass);
        $data = ['data' => []];
        $expectedDepartmentNames = [];
        for ($i = 1; $i <= $chunkSize + 1; $i++) {
            $departmentName = sprintf('New Department %d', $i);
            $data['data'][] = [
                'type'       => $entityType,
                'attributes' => ['title' => $departmentName]
            ];
            $expectedDepartmentNames[] = $departmentName;
        }
        sort($expectedDepartmentNames);
        $expectedJobs = [
            [
                'summary' => [
                    'readCount'   => $chunkSize,
                    'writeCount'  => $chunkSize,
                    'errorCount'  => 0,
                    'createCount' => $chunkSize,
                    'updateCount' => 0
                ],
            ],
            [
                'summary' => [
                    'readCount'   => 1,
                    'writeCount'  => 1,
                    'errorCount'  => 0,
                    'createCount' => 1,
                    'updateCount' => 0
                ],
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs);
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            false,
            [
                'readCount'   => $chunkSize + 1,
                'writeCount'  => $chunkSize + 1,
                'errorCount'  => 0,
                'createCount' => $chunkSize + 1,
                'updateCount' => 0
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals($expectedDepartmentNames, $this->getDepartmentNames($entities));
    }

    public function testProcessChunkForAllActionsWhenNoErrors(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $departments = $this->createDepartments(['Department 2']);
        $data = [
            'data' => [
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 1']
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => $entityType,
                    'id'         => (string)$departments[0]->getId(),
                    'attributes' => ['title' => 'Updated ' . $departments[0]->getName()]
                ]
            ]
        ];
        $expectedJobs = [
            [
                'summary' => [
                    'readCount'   => 2,
                    'writeCount'  => 2,
                    'errorCount'  => 0,
                    'createCount' => 1,
                    'updateCount' => 1
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs);
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            false,
            [
                'readCount'   => 2,
                'writeCount'  => 2,
                'errorCount'  => 0,
                'createCount' => 1,
                'updateCount' => 1
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(
            ['New Department 1', 'Updated Department 2'],
            $this->getDepartmentNames($entities)
        );
    }

    public function testProcessChunkForAllActionsWhenNoErrorsAndWithHeaderSection(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $departments = $this->createDepartments(['Department 2']);
        $data = [
            'jsonapi' => ['version' => '1.0'],
            'data'    => [
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 1']
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => $entityType,
                    'id'         => (string)$departments[0]->getId(),
                    'attributes' => ['title' => 'Updated ' . $departments[0]->getName()]
                ]
            ]
        ];
        $expectedJobs = [
            [
                'summary' => [
                    'readCount'   => 2,
                    'writeCount'  => 2,
                    'errorCount'  => 0,
                    'createCount' => 1,
                    'updateCount' => 1
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs);
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            false,
            [
                'readCount'   => 2,
                'writeCount'  => 2,
                'errorCount'  => 0,
                'createCount' => 1,
                'updateCount' => 1
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(
            ['New Department 1', 'Updated Department 2'],
            $this->getDepartmentNames($entities)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessChunkWithValidationErrors(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $departments = $this->createDepartments(['Department 2', 'Department 3']);
        $data = [
            'data' => [
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => '']
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => $entityType,
                    'id'         => (string)$departments[0]->getId(),
                    'attributes' => ['title' => '']
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => $entityType,
                    'id'         => (string)$departments[1]->getId(),
                    'attributes' => ['title' => 'Updated ' . $departments[1]->getName()]
                ]
            ]
        ];
        $expectedJobs = [
            [
                'summary' => [
                    'readCount'   => 3,
                    'writeCount'  => 0,
                    'errorCount'  => 2,
                    'createCount' => 0,
                    'updateCount' => 0
                ]
            ],
            [
                'extra_chunk' => true,
                'summary'     => [
                    'readCount'   => 1,
                    'writeCount'  => 1,
                    'errorCount'  => 0,
                    'createCount' => 0,
                    'updateCount' => 1
                ]
            ]
        ];

        $operationId = $this->processUpdateListAndValidateJobs(
            $entityClass,
            $data,
            $expectedJobs,
            null
        );

        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            true,
            [
                'readCount'   => 3,
                'writeCount'  => 1,
                'errorCount'  => 2,
                'createCount' => 0,
                'updateCount' => 1
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['Department 2', 'Updated Department 3'], $this->getDepartmentNames($entities));

        $errors = $this->getErrorManager()->readErrors($this->getFileManager(), $operationId, 0, 10);
        $expectedErrors = [
            $this->createBatchError(
                $operationId . '-1-1',
                400,
                'not blank constraint',
                'This value should not be blank.',
                0,
                '/data/0/attributes/title'
            ),
            $this->createBatchError(
                $operationId . '-1-2',
                400,
                'not blank constraint',
                'This value should not be blank.',
                1,
                '/data/1/attributes/title'
            )
        ];
        self::assertEquals($expectedErrors, $errors);
    }

    public function testTryToProcessChunkWithoutMessageQueueAndWithValidationErrors(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $departments = $this->createDepartments(['Department 2', 'Department 3']);
        $data = [
            'data' => [
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => '']
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => $entityType,
                    'id'         => (string)$departments[0]->getId(),
                    'attributes' => ['title' => '']
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => $entityType,
                    'id'         => (string)$departments[1]->getId(),
                    'attributes' => ['title' => 'Updated ' . $departments[1]->getName()]
                ]
            ]
        ];

        $operationId = $this->sendUpdateListRequestWithoutMessageQueue(
            $entityClass,
            $data
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['Department 2', 'Updated Department 3'], $this->getDepartmentNames($entities));

        $errors = $this->getErrorManager()->readErrors($this->getFileManager(), $operationId, 0, 10);
        $expectedErrors = [
            $this->createBatchError(
                $operationId . '-1-1',
                400,
                'not blank constraint',
                'This value should not be blank.',
                0,
                '/data/0/attributes/title'
            ),
            $this->createBatchError(
                $operationId . '-1-2',
                400,
                'not blank constraint',
                'This value should not be blank.',
                1,
                '/data/1/attributes/title'
            )
        ];
        self::assertEquals($expectedErrors, $errors);
    }

    public function testTryToProcessChunkWithoutMessageQueueAndWithSyncModeAndWithValidationErrors(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $departments = $this->createDepartments(['Department 2', 'Department 3']);
        $data = [
            'data' => [
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => '']
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => $entityType,
                    'id'         => (string)$departments[0]->getId(),
                    'attributes' => ['title' => '']
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => $entityType,
                    'id'         => (string)$departments[1]->getId(),
                    'attributes' => ['title' => 'Updated ' . $departments[1]->getName()]
                ]
            ]
        ];

        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(
            $entityClass,
            $data,
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/0/attributes/title']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/1/attributes/title']
                ]
            ],
            $response
        );

        $operationId = $this->getLastOperationId();

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['Department 2', 'Updated Department 3'], $this->getDepartmentNames($entities));

        $errors = $this->getErrorManager()->readErrors($this->getFileManager(), $operationId, 0, 10);
        $expectedErrors = [
            $this->createBatchError(
                $operationId . '-1-1',
                400,
                'not blank constraint',
                'This value should not be blank.',
                0,
                '/data/0/attributes/title'
            ),
            $this->createBatchError(
                $operationId . '-1-2',
                400,
                'not blank constraint',
                'This value should not be blank.',
                1,
                '/data/1/attributes/title'
            )
        ];
        self::assertEquals($expectedErrors, $errors);
    }

    public function testProcessChunkForUpdateEntitiesWithInvalidUpdateFlag(): void
    {
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $departments = $this->createDepartments(['Department 1', 'Department 2']);
        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => $entityType,
                    'id'         => (string)$departments[0]->getId(),
                    'attributes' => ['title' => 'Updated ' . $departments[0]->getName()]
                ],
                [
                    'meta'       => ['update' => 'test'],
                    'type'       => $entityType,
                    'id'         => (string)$departments[1]->getId(),
                    'attributes' => ['title' => 'Updated ' . $departments[1]->getName()]
                ]
            ]
        ];
        $expectedJobs = [
            [
                'summary' => [
                    'readCount'   => 2,
                    'writeCount'  => 0,
                    'errorCount'  => 1,
                    'createCount' => 0,
                    'updateCount' => 0
                ]
            ],
            [
                'summary' => [
                    'readCount'   => 1,
                    'writeCount'  => 1,
                    'errorCount'  => 0,
                    'createCount' => 0,
                    'updateCount' => 1
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs);
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_SUCCESS,
            1.0,
            true,
            [
                'readCount'   => 2,
                'writeCount'  => 1,
                'errorCount'  => 1,
                'createCount' => 0,
                'updateCount' => 1
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['Department 2', 'Updated Department 1'], $this->getDepartmentNames($entities));

        $errors = $this->getErrorManager()->readErrors($this->getFileManager(), $operationId, 0, 10);
        $expectedErrors = [
            $this->createBatchError(
                $operationId . '-1-1',
                400,
                'value constraint',
                'This value should be a boolean.',
                1,
                '/data/1/meta/update'
            )
        ];
        self::assertEquals($expectedErrors, $errors);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testProcessChunkWithUniqueIndexDatabaseException(): void
    {
        $this->markTestSkipped('Due to BAP-19673');
        $entityClass = TestDepartment::class;
        $entityType = $this->getEntityType($entityClass);
        $data = [
            'data' => [
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 1']
                ],
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 2']
                ],
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 2']
                ],
                [
                    'type'       => $entityType,
                    'attributes' => ['title' => 'New Department 3']
                ]
            ]
        ];
        $expectedJobs = [
            [
                'summary' => [
                    'readCount'   => 4,
                    'writeCount'  => 0,
                    'errorCount'  => 0,
                    'createCount' => 0,
                    'updateCount' => 0
                ]
            ],
            [
                'extra_chunk' => true,
                'summary'     => [
                    'readCount'   => 1,
                    'writeCount'  => 1,
                    'errorCount'  => 0,
                    'createCount' => 1,
                    'updateCount' => 0
                ]
            ],
            [
                'extra_chunk' => true,
                'summary'     => [
                    'readCount'   => 1,
                    'writeCount'  => 0,
                    'errorCount'  => 1,
                    'createCount' => 0,
                    'updateCount' => 0
                ]
            ],
            [
                'extra_chunk' => true,
                'summary'     => [
                    'readCount'   => 1,
                    'writeCount'  => 0,
                    'errorCount'  => 1,
                    'createCount' => 0,
                    'updateCount' => 0
                ]
            ],
            [
                'extra_chunk' => true,
                'summary'     => [
                    'readCount'   => 1,
                    'writeCount'  => 1,
                    'errorCount'  => 0,
                    'createCount' => 1,
                    'updateCount' => 0
                ]
            ]
        ];
        $operationId = $this->processUpdateListAndValidateJobs($entityClass, $data, $expectedJobs);
        $this->assertAsyncOperationRootJobStatus(
            $operationId,
            Job::STATUS_FAILED,
            1.0,
            true,
            [
                'readCount'   => 4,
                'writeCount'  => 2,
                'errorCount'  => 2,
                'createCount' => 2,
                'updateCount' => 0
            ]
        );

        $this->getEntityManager()->clear();
        $entities = $this->getEntityManager()->getRepository(TestDepartment::class)->findAll();
        self::assertEquals(['New Department 1', 'New Department 3'], $this->getDepartmentNames($entities));

        $errors = $this->getErrorManager()->readErrors($this->getFileManager(), $operationId, 0, 10);
        $expectedErrors = [
            $this->createBatchError(
                $operationId . '-2-1',
                500,
                'unique constraint violation exception'
            ),
            $this->createBatchError(
                $operationId . '-3-2',
                500,
                'unique constraint violation exception'
            )
        ];
        self::assertEquals($expectedErrors, $errors);
    }
}
