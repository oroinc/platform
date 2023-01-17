<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @group search
 */
class UserSearchTextFilterTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures(['@OroUserBundle/Tests/Functional/Api/DataFixtures/user_search_text_filter.yml']);
        // do the reindex because by some unknown reasons the search index is empty
        // after upgrade from old application version
        self::getContainer()->get('oro_search.search.engine.indexer')->reindex(User::class);
    }

    public function testSearchTextFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'rob', 'sort' => 'id', 'fields[users]' => 'username'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user1->id)>', 'attributes' => ['username' => 'user1']],
                    ['type' => 'users', 'id' => '<toString(@user2->id)>', 'attributes' => ['username' => 'user2']],
                    ['type' => 'users', 'id' => '<toString(@user3->id)>', 'attributes' => ['username' => 'user3']],
                    ['type' => 'users', 'id' => '<toString(@user4->id)>', 'attributes' => ['username' => 'user4']],
                    ['type' => 'users', 'id' => '<toString(@user5->id)>', 'attributes' => ['username' => 'user5']],
                    ['type' => 'users', 'id' => '<toString(@user6->id)>', 'attributes' => ['username' => 'user6']],
                    ['type' => 'users', 'id' => '<toString(@user7->id)>', 'attributes' => ['username' => 'user7']],
                    ['type' => 'users', 'id' => '<toString(@user8->id)>', 'attributes' => ['username' => 'user8']],
                    ['type' => 'users', 'id' => '<toString(@user9->id)>', 'attributes' => ['username' => 'user9']],
                    ['type' => 'users', 'id' => '<toString(@user10->id)>', 'attributes' => ['username' => 'user10']],
                ]
            ],
            $response
        );
        self::assertEquals(15, $response->headers->get('X-Include-Total-Count'));
    }

    public function testSearchTextFilterDescSort(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'rob', 'sort' => '-id', 'fields[users]' => 'username']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user15->id)>', 'attributes' => ['username' => 'user15']],
                    ['type' => 'users', 'id' => '<toString(@user14->id)>', 'attributes' => ['username' => 'user14']],
                    ['type' => 'users', 'id' => '<toString(@user13->id)>', 'attributes' => ['username' => 'user13']],
                    ['type' => 'users', 'id' => '<toString(@user12->id)>', 'attributes' => ['username' => 'user12']],
                    ['type' => 'users', 'id' => '<toString(@user11->id)>', 'attributes' => ['username' => 'user11']],
                    ['type' => 'users', 'id' => '<toString(@user10->id)>', 'attributes' => ['username' => 'user10']],
                    ['type' => 'users', 'id' => '<toString(@user9->id)>', 'attributes' => ['username' => 'user9']],
                    ['type' => 'users', 'id' => '<toString(@user8->id)>', 'attributes' => ['username' => 'user8']],
                    ['type' => 'users', 'id' => '<toString(@user7->id)>', 'attributes' => ['username' => 'user7']],
                    ['type' => 'users', 'id' => '<toString(@user6->id)>', 'attributes' => ['username' => 'user6']],
                ]
            ],
            $response
        );
    }

    public function testSearchTextFilterWithoutSortFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'rob', 'page[size]' => 100]
        );
        $responseContent = self::jsonToArray($response->getContent());
        $this->assertCount(15, $responseContent['data']);
    }

    public function testPaginationLinksForFirstPage(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'rob', 'sort' => 'id', 'page' => ['size' => 2]],
            ['HTTP_HATEOAS' => true]
        );
        $url = '{baseUrl}/users';
        $urlWithFilter = $url . '?filter%5BsearchText%5D=rob';
        $this->assertResponseContains(
            $this->getExpectedContentWithPaginationLinks([
                'data'  => [
                    ['type' => 'users', 'id' => '<toString(@user1->id)>', 'attributes' => ['username' => 'user1']],
                    ['type' => 'users', 'id' => '<toString(@user2->id)>', 'attributes' => ['username' => 'user2']],
                ],
                'links' => [
                    'self' => $url,
                    'next' => $urlWithFilter . '&page%5Bsize%5D=2&page%5Bnumber%5D=2&sort=id',
                ]
            ]),
            $response
        );
    }

    public function testPaginationLinksForLastPage(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'rob', 'sort' => 'id', 'page' => ['size' => 2, 'number' => 8]],
            ['HTTP_HATEOAS' => true]
        );
        $url = '{baseUrl}/users';
        $urlWithFilter = $url . '?filter%5BsearchText%5D=rob';
        $this->assertResponseContains(
            $this->getExpectedContentWithPaginationLinks([
                'data'  => [
                    ['type' => 'users', 'id' => '<toString(@user15->id)>', 'attributes' => ['username' => 'user15']],
                ],
                'links' => [
                    'self'  => $url,
                    'first' => $urlWithFilter . '&page%5Bsize%5D=2&sort=id',
                    'prev'  => $urlWithFilter . '&page%5Bnumber%5D=7&page%5Bsize%5D=2&sort=id',
                ]
            ]),
            $response
        );
    }

    public function testPaginationLinksForMediumPage(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'rob', 'sort' => 'id', 'page' => ['size' => 2, 'number' => 3]],
            ['HTTP_HATEOAS' => true]
        );
        $url = '{baseUrl}/users';
        $urlWithFilter = $url . '?filter%5BsearchText%5D=rob';
        $this->assertResponseContains(
            $this->getExpectedContentWithPaginationLinks([
                'data'  => [
                    ['type' => 'users', 'id' => '<toString(@user5->id)>', 'attributes' => ['username' => 'user5']],
                    ['type' => 'users', 'id' => '<toString(@user6->id)>', 'attributes' => ['username' => 'user6']],
                ],
                'links' => [
                    'self'  => $url,
                    'first' => $urlWithFilter . '&page%5Bsize%5D=2&sort=id',
                    'prev'  => $urlWithFilter . '&page%5Bnumber%5D=2&page%5Bsize%5D=2&sort=id',
                    'next'  => $urlWithFilter . '&page%5Bnumber%5D=4&page%5Bsize%5D=2&sort=id',
                ]
            ]),
            $response
        );
    }

    public function testTryToUseSearchTextFilterWithAnotherFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter' => ['searchText' => 'rob', 'username' => 'user1']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'This filter cannot be used together with other filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToUseSearchTextFilterWithSortingByFieldUndefinedInSearchIndex(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'rob', 'sort' => 'googleId'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'sort constraint',
                'detail' => 'Sorting by "googleId" field is not supported.',
                'source' => ['parameter' => 'sort']
            ],
            $response
        );
    }

    public function testTryToUseSearchTextFilterWhenSearchCapabilityDisabled(): void
    {
        $this->updateRolePermissionForAction(User::ROLE_ADMINISTRATOR, 'oro_search', false);

        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'rob', 'sort' => 'id', 'fields[users]' => 'username'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'This filter cannot be used because the search capability is disabled.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }
}
