<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDepartment;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

/**
 * @dbIsolationPerTest
 */
class DeleteListTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroApiBundle/Tests/Functional/DataFixtures/delete_list.yml']);
    }

    public function testDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => $this->getEntityType(TestDepartment::class)],
            ['filter' => ['id' => '<toString(@TestDepartment1->id)>']],
            ['HTTP_X-Include' => 'totalCount;deletedCount']
        );

        self::assertEquals(1, $response->headers->get('X-Include-Total-Count'), 'totalCount');
        self::assertEquals(1, $response->headers->get('X-Include-Deleted-Count'), 'deletedCount');
        self::assertCount(102, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());
    }

    public function testDeleteListWithoutFilters()
    {
        $response = $this->cdelete(
            ['entity' => $this->getEntityType(TestDepartment::class)],
            [],
            ['HTTP_X-Include' => 'totalCount;deletedCount'],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'At least one filter must be provided.'
            ],
            $response
        );
    }

    public function testDefaultMaxDeleteEntitiesLimit()
    {
        $response = $this->cdelete(
            ['entity' => $this->getEntityType(TestDepartment::class)],
            ['filter' => ['id' => ['gt' => '0']]],
            ['HTTP_X-Include' => 'totalCount;deletedCount']
        );

        self::assertEquals(103, $response->headers->get('X-Include-Total-Count'), 'totalCount');
        self::assertEquals(100, $response->headers->get('X-Include-Deleted-Count'), 'deletedCount');
        self::assertCount(3, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());
    }

    public function testCustomMaxDeleteEntitiesLimit()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'actions' => [
                    'delete_list' => [
                        'max_results' => 10
                    ]
                ]
            ]
        );

        $response = $this->cdelete(
            ['entity' => $this->getEntityType(TestDepartment::class)],
            ['filter' => ['id' => ['gt' => '0']]],
            ['HTTP_X-Include' => 'totalCount;deletedCount']
        );

        self::assertEquals(103, $response->headers->get('X-Include-Total-Count'), 'totalCount');
        self::assertEquals(10, $response->headers->get('X-Include-Deleted-Count'), 'deletedCount');
        self::assertCount(93, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());
    }

    public function testWithoutMaxDeleteEntitiesLimit()
    {
        $this->appendEntityConfig(
            TestDepartment::class,
            [
                'actions' => [
                    'delete_list' => [
                        'max_results' => -1
                    ]
                ]
            ]
        );

        $response = $this->cdelete(
            ['entity' => $this->getEntityType(TestDepartment::class)],
            ['filter' => ['id' => ['gt' => '0']]],
            ['HTTP_X-Include' => 'totalCount;deletedCount']
        );

        self::assertEquals(103, $response->headers->get('X-Include-Total-Count'), 'totalCount');
        self::assertEquals(103, $response->headers->get('X-Include-Deleted-Count'), 'deletedCount');
        self::assertCount(0, $this->getEntityManager()->getRepository(TestDepartment::class)->findAll());
    }
}
