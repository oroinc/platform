<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiBatch;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadOperationsErrorsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AsyncOperationErrorsTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadOperationsErrorsData::class]);
    }

    private function updateIds(string $expectedContentFileName, int $operationId): array
    {
        $expectedContent = $this->getResponseData($expectedContentFileName);
        foreach ($expectedContent['data'] as $index => $item) {
            $expectedContent['data'][$index]['id'] = str_replace('{operationId}', (string)$operationId, $item['id']);
        }

        return $expectedContent;
    }

    public function testGetErrorsForOperationWithoutErrors()
    {
        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>', 'association' => 'errors']
        );
        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetErrorsForOperationWithErrors()
    {
        $operationId = $this->getReference('user_operation2')->getId();
        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => (string)$operationId, 'association' => 'errors']
        );
        $this->assertResponseContains(
            $this->updateIds('get_async_operation_errors.yml', $operationId),
            $response
        );
    }

    public function testGetFirstPageErrorsForOperationWithErrors()
    {
        $operationId = $this->getReference('user_operation2')->getId();
        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => (string)$operationId, 'association' => 'errors'],
            ['page[size]' => 2, 'page[number]' => 1]
        );
        $this->assertResponseContains(
            $this->updateIds('get_async_operation_errors_first_page.yml', $operationId),
            $response
        );
    }

    public function testGetSecondPageErrorsForOperationWithErrors()
    {
        $operationId = $this->getReference('user_operation2')->getId();
        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => (string)$operationId, 'association' => 'errors'],
            ['page[size]' => 2, 'page[number]' => 2]
        );
        $this->assertResponseContains(
            $this->updateIds('get_async_operation_errors_second_page.yml', $operationId),
            $response
        );
    }

    public function testGetLastPageErrorsForOperationWithErrors()
    {
        $operationId = $this->getReference('user_operation2')->getId();
        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => (string)$operationId, 'association' => 'errors'],
            ['page[size]' => 2, 'page[number]' => 4]
        );
        $this->assertResponseContains(
            $this->updateIds('get_async_operation_errors_last_page.yml', $operationId),
            $response
        );
    }

    public function testPaginationLinksForFirstPage(): void
    {
        $operationId = $this->getReference('user_operation2')->getId();
        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => (string)$operationId, 'association' => 'errors'],
            ['page[size]' => 2, 'page[number]' => 1],
            ['HTTP_HATEOAS' => true]
        );
        $url = sprintf('{baseUrl}/asyncoperations/%d/errors', $operationId);
        $expectedContent = $this->updateIds('get_async_operation_errors_first_page.yml', $operationId);
        $expectedContent['links'] = $this->getExpectedContentWithPaginationLinks([
            'self' => $url,
            'next' => $url . '?page%5Bnumber%5D=2&page%5Bsize%5D=2',
        ]);
        $this->assertResponseContains($expectedContent, $response);
    }

    public function testPaginationLinksForSecondPage(): void
    {
        $operationId = $this->getReference('user_operation2')->getId();
        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => (string)$operationId, 'association' => 'errors'],
            ['page[size]' => 2, 'page[number]' => 2],
            ['HTTP_HATEOAS' => true]
        );
        $url = sprintf('{baseUrl}/asyncoperations/%d/errors', $operationId);
        $expectedContent = $this->updateIds('get_async_operation_errors_second_page.yml', $operationId);
        $expectedContent['links'] = $this->getExpectedContentWithPaginationLinks([
            'self'  => $url,
            'first' => $url . '?page%5Bsize%5D=2',
            'prev'  => $url . '?page%5Bsize%5D=2',
            'next'  => $url . '?page%5Bnumber%5D=3&page%5Bsize%5D=2',
        ]);
        $this->assertResponseContains($expectedContent, $response);
    }

    public function testPaginationLinksForLastPage(): void
    {
        $operationId = $this->getReference('user_operation2')->getId();
        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => (string)$operationId, 'association' => 'errors'],
            ['page[size]' => 2, 'page[number]' => 4],
            ['HTTP_HATEOAS' => true]
        );
        $url = sprintf('{baseUrl}/asyncoperations/%d/errors', $operationId);
        $expectedContent = $this->updateIds('get_async_operation_errors_last_page.yml', $operationId);
        $expectedContent['links'] = $this->getExpectedContentWithPaginationLinks([
            'self'  => $url,
            'first' => $url . '?page%5Bsize%5D=2',
            'prev'  => $url . '?page%5Bnumber%5D=3&page%5Bsize%5D=2',
        ]);
        $this->assertResponseContains($expectedContent, $response);
    }

    public function testGetErrorsForOperationWithoutErrorsOnOwnOperationOnUserAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::BASIC_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
            ]
        );

        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>', 'association' => 'errors']
        );
        $this->assertResponseContains(['data' => []], $response);
    }

    public function testTryToGetErrorsWithoutPermission()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::NONE_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
            ]
        );

        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>', 'association' => 'errors'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetErrorsOnNotOwnOperationOnUserAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            AsyncOperation::class,
            [
                'VIEW'   => AccessLevel::BASIC_LEVEL,
                'CREATE' => AccessLevel::NONE_LEVEL,
                'DELETE' => AccessLevel::NONE_LEVEL,
                'ASSIGN' => AccessLevel::NONE_LEVEL,
                'EDIT'   => AccessLevel::NONE_LEVEL,
            ]
        );

        $operationId = $this->getReference('subordinate_bu_user_operation')->getId();
        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => (string)$operationId, 'association' => 'errors'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_FORBIDDEN);
    }

    public function testTryToGetErrorsForNotExistingOperation()
    {
        $response = $this->getSubresource(
            ['entity' => 'asyncoperations', 'id' => (string)99999999, 'association' => 'errors'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreateError()
    {
        $response = $this->postSubresource(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>', 'association' => 'errors'],
            [],
            [],
            false
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateError()
    {
        $response = $this->patchSubresource(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>', 'association' => 'errors'],
            [],
            [],
            false
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteError()
    {
        $response = $this->deleteSubresource(
            ['entity' => 'asyncoperations', 'id' => '<toString(@user_operation1->id)>', 'association' => 'errors'],
            [],
            [],
            false
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }
}
