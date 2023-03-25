<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;

/**
 * Tests that events for the "customize_form_data" action are dispatched properly.
 * @dbIsolationPerTest
 */
class CustomizeFormDataExtendabilityTest extends RestJsonApiUpdateListTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/customize_form_data_extendability.yml']);
    }

    private function getCustomizeFormDataLogs(): array
    {
        $logger = $this->getCustomizeFormDataLogger();
        if (null === $logger) {
            return [];
        }

        $messages = [];
        $logs = $logger->cleanLogs();
        foreach ($logs as $entry) {
            $params = $entry[2];
            unset($params['requestType'], $params['version']);
            $messages[] = [$entry[1], $params];
        }

        return $messages;
    }

    private function getLogItem(string $eventName, string $entityClass, string $parentAction): array
    {
        return [
            $this->getLogMessage($eventName, $entityClass),
            $this->getLogMessageParameters($parentAction, null, null)
        ];
    }

    private function getLogMessage(string $eventName, string $entityClass): string
    {
        return sprintf(
            'Process "%s" event of "customize_form_data" action for "%s".',
            $eventName,
            $entityClass
        );
    }

    private function getLogMessageParameters(
        string $parentAction,
        ?string $propertyPath,
        ?string $rootClassName
    ): array {
        return [
            'parentAction'  => $parentAction,
            'propertyPath'  => $propertyPath,
            'rootClassName' => $rootClassName
        ];
    }

    public function testCreateWithoutIncludes(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);

        $this->post(
            ['entity' => $departmentEntityType],
            [
                'data' => [
                    'type'       => $departmentEntityType,
                    'attributes' => [
                        'title' => 'test department'
                    ]
                ]
            ]
        );

        self::assertEquals(
            [
                $this->getLogItem('pre_submit', TestDepartment::class, 'create'),
                $this->getLogItem('submit', TestDepartment::class, 'create'),
                $this->getLogItem('post_submit', TestDepartment::class, 'create'),
                $this->getLogItem('pre_validate', TestDepartment::class, 'create'),
                $this->getLogItem('post_validate', TestDepartment::class, 'create'),
                $this->getLogItem('pre_flush_data', TestDepartment::class, 'create'),
                $this->getLogItem('post_flush_data', TestDepartment::class, 'create'),
                $this->getLogItem('post_save_data', TestDepartment::class, 'create')
            ],
            $this->getCustomizeFormDataLogs()
        );
    }

    public function testCreateWithIncludes(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $this->post(
            ['entity' => $departmentEntityType],
            [
                'data'     => [
                    'type'          => $departmentEntityType,
                    'attributes'    => [
                        'title' => 'test department'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => 'new_employee1']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $employeeEntityType,
                        'id'         => 'new_employee1',
                        'attributes' => ['name' => 'New Employee 1']
                    ]
                ]
            ]
        );

        self::assertEquals(
            [
                $this->getLogItem('pre_submit', TestEmployee::class, 'create'),
                $this->getLogItem('submit', TestEmployee::class, 'create'),
                $this->getLogItem('post_submit', TestEmployee::class, 'create'),
                $this->getLogItem('pre_submit', TestDepartment::class, 'create'),
                $this->getLogItem('submit', TestDepartment::class, 'create'),
                $this->getLogItem('post_submit', TestDepartment::class, 'create'),
                $this->getLogItem('pre_validate', TestEmployee::class, 'create'),
                $this->getLogItem('pre_validate', TestDepartment::class, 'create'),
                $this->getLogItem('post_validate', TestEmployee::class, 'create'),
                $this->getLogItem('post_validate', TestDepartment::class, 'create'),
                $this->getLogItem('pre_flush_data', TestEmployee::class, 'create'),
                $this->getLogItem('pre_flush_data', TestDepartment::class, 'create'),
                $this->getLogItem('post_flush_data', TestEmployee::class, 'create'),
                $this->getLogItem('post_flush_data', TestDepartment::class, 'create'),
                $this->getLogItem('post_save_data', TestEmployee::class, 'create'),
                $this->getLogItem('post_save_data', TestDepartment::class, 'create')
            ],
            $this->getCustomizeFormDataLogs()
        );
    }

    public function testUpdateWithoutIncludes(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);

        $this->patch(
            ['entity' => $departmentEntityType, 'id' => '<toString(@department1->id)>'],
            [
                'data' => [
                    'type'       => $departmentEntityType,
                    'id'         => '<toString(@department1->id)>',
                    'attributes' => [
                        'title' => 'test department'
                    ]
                ]
            ]
        );

        self::assertEquals(
            [
                $this->getLogItem('pre_submit', TestDepartment::class, 'update'),
                $this->getLogItem('submit', TestDepartment::class, 'update'),
                $this->getLogItem('post_submit', TestDepartment::class, 'update'),
                $this->getLogItem('pre_validate', TestDepartment::class, 'update'),
                $this->getLogItem('post_validate', TestDepartment::class, 'update'),
                $this->getLogItem('pre_flush_data', TestDepartment::class, 'update'),
                $this->getLogItem('post_flush_data', TestDepartment::class, 'update'),
                $this->getLogItem('post_save_data', TestDepartment::class, 'update')
            ],
            $this->getCustomizeFormDataLogs()
        );
    }

    public function testUpdateWithIncludes(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $this->patch(
            ['entity' => $departmentEntityType, 'id' => '<toString(@department1->id)>'],
            [
                'data'     => [
                    'type'          => $departmentEntityType,
                    'id'            => '<toString(@department1->id)>',
                    'attributes'    => [
                        'title' => 'test department'
                    ],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => 'new_employee1']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $employeeEntityType,
                        'id'         => 'new_employee1',
                        'attributes' => ['name' => 'New Employee 1']
                    ]
                ]
            ]
        );

        self::assertEquals(
            [
                $this->getLogItem('pre_submit', TestEmployee::class, 'create'),
                $this->getLogItem('submit', TestEmployee::class, 'create'),
                $this->getLogItem('post_submit', TestEmployee::class, 'create'),
                $this->getLogItem('pre_submit', TestDepartment::class, 'update'),
                $this->getLogItem('submit', TestDepartment::class, 'update'),
                $this->getLogItem('post_submit', TestDepartment::class, 'update'),
                $this->getLogItem('pre_validate', TestEmployee::class, 'create'),
                $this->getLogItem('pre_validate', TestDepartment::class, 'update'),
                $this->getLogItem('post_validate', TestEmployee::class, 'create'),
                $this->getLogItem('post_validate', TestDepartment::class, 'update'),
                $this->getLogItem('pre_flush_data', TestEmployee::class, 'create'),
                $this->getLogItem('pre_flush_data', TestDepartment::class, 'update'),
                $this->getLogItem('post_flush_data', TestEmployee::class, 'create'),
                $this->getLogItem('post_flush_data', TestDepartment::class, 'update'),
                $this->getLogItem('post_save_data', TestEmployee::class, 'create'),
                $this->getLogItem('post_save_data', TestDepartment::class, 'update')
            ],
            $this->getCustomizeFormDataLogs()
        );
    }

    public function testUpdateRelationship(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $this->patchRelationship(
            ['entity' => $departmentEntityType, 'id' => '<toString(@department1->id)>', 'association' => 'staff'],
            [
                'data' => [
                    ['type' => $employeeEntityType, 'id' => '<toString(@employee2->id)>']
                ]
            ]
        );

        self::assertEquals(
            [
                $this->getLogItem('pre_submit', TestDepartment::class, 'update_relationship'),
                $this->getLogItem('submit', TestDepartment::class, 'update_relationship'),
                $this->getLogItem('post_submit', TestDepartment::class, 'update_relationship'),
                $this->getLogItem('pre_validate', TestDepartment::class, 'update_relationship'),
                $this->getLogItem('post_validate', TestDepartment::class, 'update_relationship'),
                $this->getLogItem('pre_flush_data', TestDepartment::class, 'update_relationship'),
                $this->getLogItem('post_flush_data', TestDepartment::class, 'update_relationship'),
                $this->getLogItem('post_save_data', TestDepartment::class, 'update_relationship')
            ],
            $this->getCustomizeFormDataLogs()
        );
    }

    public function testAddRelationship(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $this->postRelationship(
            ['entity' => $departmentEntityType, 'id' => '<toString(@department1->id)>', 'association' => 'staff'],
            [
                'data' => [
                    ['type' => $employeeEntityType, 'id' => '<toString(@employee2->id)>']
                ]
            ]
        );

        self::assertEquals(
            [
                $this->getLogItem('pre_submit', TestDepartment::class, 'add_relationship'),
                $this->getLogItem('submit', TestDepartment::class, 'add_relationship'),
                $this->getLogItem('post_submit', TestDepartment::class, 'add_relationship'),
                $this->getLogItem('pre_validate', TestDepartment::class, 'add_relationship'),
                $this->getLogItem('post_validate', TestDepartment::class, 'add_relationship'),
                $this->getLogItem('pre_flush_data', TestDepartment::class, 'add_relationship'),
                $this->getLogItem('post_flush_data', TestDepartment::class, 'add_relationship'),
                $this->getLogItem('post_save_data', TestDepartment::class, 'add_relationship')
            ],
            $this->getCustomizeFormDataLogs()
        );
    }

    public function testDeleteRelationship(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $this->deleteRelationship(
            ['entity' => $departmentEntityType, 'id' => '<toString(@department1->id)>', 'association' => 'staff'],
            [
                'data' => [
                    ['type' => $employeeEntityType, 'id' => '<toString(@employee1->id)>']
                ]
            ]
        );

        self::assertEquals(
            [
                $this->getLogItem('pre_submit', TestDepartment::class, 'delete_relationship'),
                $this->getLogItem('submit', TestDepartment::class, 'delete_relationship'),
                $this->getLogItem('post_submit', TestDepartment::class, 'delete_relationship'),
                $this->getLogItem('pre_validate', TestDepartment::class, 'delete_relationship'),
                $this->getLogItem('post_validate', TestDepartment::class, 'delete_relationship'),
                $this->getLogItem('pre_flush_data', TestDepartment::class, 'delete_relationship'),
                $this->getLogItem('post_flush_data', TestDepartment::class, 'delete_relationship'),
                $this->getLogItem('post_save_data', TestDepartment::class, 'delete_relationship')
            ],
            $this->getCustomizeFormDataLogs()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testUpdateList(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);

        $operationId = $this->sendUpdateListRequest(TestDepartment::class, [
            'data' => [
                [
                    'type' => $departmentEntityType,
                    'attributes' => ['title' => 'New Department 1'],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => 'new_employee1']
                            ]
                        ]
                    ]
                ],
                [
                    'type' => $departmentEntityType,
                    'attributes' => ['title' => 'New Department 2'],
                    'relationships' => [
                        'staff' => [
                            'data' => [
                                ['type' => $employeeEntityType, 'id' => 'new_employee2'],
                                ['type' => $employeeEntityType, 'id' => 'new_employee3']
                            ]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee1',
                    'attributes' => ['name' => 'New Employee 1']
                ],
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee2',
                    'attributes' => ['name' => 'New Employee 2']
                ],
                [
                    'type' => $employeeEntityType,
                    'id' => 'new_employee3',
                    'attributes' => ['name' => 'New Employee 3']
                ]
            ]
        ]);

        $tokenStorage = $this->getTokenStorage();
        $token = $this->getTokenStorage()->getToken();

        $this->consumeMessages();

        //refresh token after resetting in consumer
        $tokenStorage->setToken($token);

        /** @var ContainerResetExtension $resetExtension */
        $resetExtension = $this->getContainer()->get('oro_message_queue.consumption.container_reset_extension');

        //we have to not clear a logger after the processor execution to check logs
        $resetExtension->setPersistentProcessors([
            'oro_api.batch.async.update_list.process_chunk'
        ]);
        $this->consumeMessages();

        $this->assertAsyncOperationErrors([], $operationId);

        self::assertEquals(
            [
                $this->getLogItem('pre_submit', TestEmployee::class, 'create'),
                $this->getLogItem('submit', TestEmployee::class, 'create'),
                $this->getLogItem('post_submit', TestEmployee::class, 'create'),
                $this->getLogItem('pre_submit', TestDepartment::class, 'create'),
                $this->getLogItem('submit', TestDepartment::class, 'create'),
                $this->getLogItem('post_submit', TestDepartment::class, 'create'),
                $this->getLogItem('pre_validate', TestEmployee::class, 'create'),
                $this->getLogItem('pre_validate', TestDepartment::class, 'create'),
                $this->getLogItem('post_validate', TestEmployee::class, 'create'),
                $this->getLogItem('post_validate', TestDepartment::class, 'create'),
                $this->getLogItem('pre_submit', TestEmployee::class, 'create'),
                $this->getLogItem('submit', TestEmployee::class, 'create'),
                $this->getLogItem('post_submit', TestEmployee::class, 'create'),
                $this->getLogItem('pre_submit', TestEmployee::class, 'create'),
                $this->getLogItem('submit', TestEmployee::class, 'create'),
                $this->getLogItem('post_submit', TestEmployee::class, 'create'),
                $this->getLogItem('pre_submit', TestDepartment::class, 'create'),
                $this->getLogItem('submit', TestDepartment::class, 'create'),
                $this->getLogItem('post_submit', TestDepartment::class, 'create'),
                $this->getLogItem('pre_validate', TestEmployee::class, 'create'),
                $this->getLogItem('pre_validate', TestEmployee::class, 'create'),
                $this->getLogItem('pre_validate', TestDepartment::class, 'create'),
                $this->getLogItem('post_validate', TestEmployee::class, 'create'),
                $this->getLogItem('post_validate', TestEmployee::class, 'create'),
                $this->getLogItem('post_validate', TestDepartment::class, 'create'),
                $this->getLogItem('pre_flush_data', TestEmployee::class, 'create'),
                $this->getLogItem('pre_flush_data', TestDepartment::class, 'create'),
                $this->getLogItem('pre_flush_data', TestEmployee::class, 'create'),
                $this->getLogItem('pre_flush_data', TestEmployee::class, 'create'),
                $this->getLogItem('pre_flush_data', TestDepartment::class, 'create'),
                $this->getLogItem('post_flush_data', TestEmployee::class, 'create'),
                $this->getLogItem('post_flush_data', TestDepartment::class, 'create'),
                $this->getLogItem('post_flush_data', TestEmployee::class, 'create'),
                $this->getLogItem('post_flush_data', TestEmployee::class, 'create'),
                $this->getLogItem('post_flush_data', TestDepartment::class, 'create'),
                $this->getLogItem('post_save_data', TestEmployee::class, 'create'),
                $this->getLogItem('post_save_data', TestDepartment::class, 'create'),
                $this->getLogItem('post_save_data', TestEmployee::class, 'create'),
                $this->getLogItem('post_save_data', TestEmployee::class, 'create'),
                $this->getLogItem('post_save_data', TestDepartment::class, 'create')
            ],
            $this->getCustomizeFormDataLogs()
        );
    }
}
