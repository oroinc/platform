<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiValidation;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEmployee;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor\AddFormErrorInFlushDataEvents;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class HandlingFormErrorAddedInFlushDataEventsTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/customize_form_data_extendability.yml']);
    }

    public function testTryToCreateWhenFormErrorAddedInPreFlushDataForPrimaryEntity(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $departmentName =
            AddFormErrorInFlushDataEvents::FORM_ERROR_PREFIX
            . CustomizeFormDataContext::EVENT_PRE_FLUSH_DATA;

        $response = $this->post(
            ['entity' => $departmentEntityType],
            [
                'data' => [
                    'type'       => $departmentEntityType,
                    'attributes' => ['title' => $departmentName]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'pre flush data constraint',
                'detail' => 'Invalid Value',
                'source' => ['pointer' => '/data/attributes/title']
            ],
            $response
        );
    }

    public function testTryToCreateWhenFormErrorAddedInPostFlushDataForPrimaryEntity(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $departmentName =
            AddFormErrorInFlushDataEvents::FORM_ERROR_PREFIX
            . CustomizeFormDataContext::EVENT_POST_FLUSH_DATA;

        $response = $this->post(
            ['entity' => $departmentEntityType],
            [
                'data' => [
                    'type'       => $departmentEntityType,
                    'attributes' => ['title' => $departmentName]
                ]
            ],
            [],
            false
        );
        $this->getEntityManager()->clear();

        $this->assertResponseValidationError(
            [
                'title'  => 'post flush data constraint',
                'detail' => 'Invalid Value',
                'source' => ['pointer' => '/data/attributes/title']
            ],
            $response
        );
    }

    public function testTryToCreateWhenFormErrorAddedInPostSaveDataForPrimaryEntity(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $departmentName =
            AddFormErrorInFlushDataEvents::FORM_ERROR_PREFIX
            . CustomizeFormDataContext::EVENT_POST_SAVE_DATA;

        $response = $this->post(
            ['entity' => $departmentEntityType],
            [
                'data' => [
                    'type'       => $departmentEntityType,
                    'attributes' => ['title' => $departmentName]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'post save data constraint',
                'detail' => 'Invalid Value',
                'source' => ['pointer' => '/data/attributes/title']
            ],
            $response
        );
    }

    public function testTryToCreateWhenFormErrorAddedInPreFlushDataForIncludedEntity(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $departmentName =
            AddFormErrorInFlushDataEvents::FORM_ERROR_PREFIX
            . CustomizeFormDataContext::EVENT_PRE_FLUSH_DATA;

        $response = $this->post(
            ['entity' => $employeeEntityType],
            [
                'data'     => [
                    'type'          => $employeeEntityType,
                    'attributes'    => ['name' => 'New Employee'],
                    'relationships' => [
                        'department' => ['data' => ['type' => $departmentEntityType, 'id' => 'new_department']]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $departmentEntityType,
                        'id'         => 'new_department',
                        'attributes' => ['title' => $departmentName]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'pre flush data constraint',
                'detail' => 'Invalid Value',
                'source' => ['pointer' => '/included/0/attributes/title']
            ],
            $response
        );
    }

    public function testTryToCreateWhenFormErrorAddedInPostFlushDataForIncludedEntity(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $departmentName =
            AddFormErrorInFlushDataEvents::FORM_ERROR_PREFIX
            . CustomizeFormDataContext::EVENT_POST_FLUSH_DATA;

        $response = $this->post(
            ['entity' => $employeeEntityType],
            [
                'data'     => [
                    'type'          => $employeeEntityType,
                    'attributes'    => ['name' => 'New Employee'],
                    'relationships' => [
                        'department' => ['data' => ['type' => $departmentEntityType, 'id' => 'new_department']]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $departmentEntityType,
                        'id'         => 'new_department',
                        'attributes' => ['title' => $departmentName]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'post flush data constraint',
                'detail' => 'Invalid Value',
                'source' => ['pointer' => '/included/0/attributes/title']
            ],
            $response
        );
    }

    public function testTryToCreateWhenFormErrorAddedInPostSaveDataForIncludedEntity(): void
    {
        $departmentEntityType = $this->getEntityType(TestDepartment::class);
        $employeeEntityType = $this->getEntityType(TestEmployee::class);
        $departmentName =
            AddFormErrorInFlushDataEvents::FORM_ERROR_PREFIX
            . CustomizeFormDataContext::EVENT_POST_SAVE_DATA;

        $response = $this->post(
            ['entity' => $employeeEntityType],
            [
                'data'     => [
                    'type'          => $employeeEntityType,
                    'attributes'    => ['name' => 'New Employee'],
                    'relationships' => [
                        'department' => ['data' => ['type' => $departmentEntityType, 'id' => 'new_department']]
                    ]
                ],
                'included' => [
                    [
                        'type'       => $departmentEntityType,
                        'id'         => 'new_department',
                        'attributes' => ['title' => $departmentName]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'post save data constraint',
                'detail' => 'Invalid Value',
                'source' => ['pointer' => '/included/0/attributes/title']
            ],
            $response
        );
    }
}
