<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @group search
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UserSearchTextFilterTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    #[\Override]
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
            ['filter[searchText]' => 'smith', 'sort' => 'username', 'fields[users]' => 'username'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user11->id)>', 'attributes' => ['username' => 'user11']],
                    ['type' => 'users', 'id' => '<toString(@user12->id)>', 'attributes' => ['username' => 'user12']],
                    ['type' => 'users', 'id' => '<toString(@user13->id)>', 'attributes' => ['username' => 'user13']],
                    ['type' => 'users', 'id' => '<toString(@user14->id)>', 'attributes' => ['username' => 'user14']],
                    ['type' => 'users', 'id' => '<toString(@user15->id)>', 'attributes' => ['username' => 'user15']],
                    ['type' => 'users', 'id' => '<toString(@user16->id)>', 'attributes' => ['username' => 'user16']],
                    ['type' => 'users', 'id' => '<toString(@user17->id)>', 'attributes' => ['username' => 'user17']],
                    ['type' => 'users', 'id' => '<toString(@user18->id)>', 'attributes' => ['username' => 'user18']],
                    ['type' => 'users', 'id' => '<toString(@user19->id)>', 'attributes' => ['username' => 'user19']],
                    ['type' => 'users', 'id' => '<toString(@user21->id)>', 'attributes' => ['username' => 'user21']],
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
            ['filter[searchText]' => 'smith', 'sort' => '-username', 'fields[users]' => 'username']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user26->id)>', 'attributes' => ['username' => 'user26']],
                    ['type' => 'users', 'id' => '<toString(@user25->id)>', 'attributes' => ['username' => 'user25']],
                    ['type' => 'users', 'id' => '<toString(@user24->id)>', 'attributes' => ['username' => 'user24']],
                    ['type' => 'users', 'id' => '<toString(@user23->id)>', 'attributes' => ['username' => 'user23']],
                    ['type' => 'users', 'id' => '<toString(@user22->id)>', 'attributes' => ['username' => 'user22']],
                    ['type' => 'users', 'id' => '<toString(@user21->id)>', 'attributes' => ['username' => 'user21']],
                    ['type' => 'users', 'id' => '<toString(@user19->id)>', 'attributes' => ['username' => 'user19']],
                    ['type' => 'users', 'id' => '<toString(@user18->id)>', 'attributes' => ['username' => 'user18']],
                    ['type' => 'users', 'id' => '<toString(@user17->id)>', 'attributes' => ['username' => 'user17']],
                    ['type' => 'users', 'id' => '<toString(@user16->id)>', 'attributes' => ['username' => 'user16']],
                ]
            ],
            $response
        );
    }

    public function testSearchTextFilterWithoutSortFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'smith', 'page[size]' => 100]
        );
        $responseContent = self::jsonToArray($response->getContent());
        $this->assertCount(15, $responseContent['data']);
    }

    public function testPaginationLinksForFirstPage(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'smith', 'sort' => 'username', 'page' => ['size' => 2]],
            ['HTTP_HATEOAS' => true]
        );
        $url = '{baseUrl}/users';
        $urlWithFilter = $url . '?filter%5BsearchText%5D=smith';
        $this->assertResponseContains(
            [
                'data'  => [
                    ['type' => 'users', 'id' => '<toString(@user11->id)>', 'attributes' => ['username' => 'user11']],
                    ['type' => 'users', 'id' => '<toString(@user12->id)>', 'attributes' => ['username' => 'user12']],
                ],
                'links' => [
                    'self' => $url,
                    'next' => $urlWithFilter . '&page%5Bsize%5D=2&page%5Bnumber%5D=2&sort=username',
                ]
            ],
            $response
        );
    }

    public function testPaginationLinksForLastPage(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'smith', 'sort' => 'username', 'page' => ['size' => 2, 'number' => 8]],
            ['HTTP_HATEOAS' => true]
        );
        $url = '{baseUrl}/users';
        $urlWithFilter = $url . '?filter%5BsearchText%5D=smith';
        $this->assertResponseContains(
            [
                'data'  => [
                    ['type' => 'users', 'id' => '<toString(@user26->id)>', 'attributes' => ['username' => 'user26']],
                ],
                'links' => [
                    'self'  => $url,
                    'first' => $urlWithFilter . '&page%5Bsize%5D=2&sort=username',
                    'prev'  => $urlWithFilter . '&page%5Bnumber%5D=7&page%5Bsize%5D=2&sort=username',
                ]
            ],
            $response
        );
    }

    public function testPaginationLinksForMediumPage(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'smith', 'sort' => 'username', 'page' => ['size' => 2, 'number' => 3]],
            ['HTTP_HATEOAS' => true]
        );
        $url = '{baseUrl}/users';
        $urlWithFilter = $url . '?filter%5BsearchText%5D=smith';
        $this->assertResponseContains(
            [
                'data'  => [
                    ['type' => 'users', 'id' => '<toString(@user15->id)>', 'attributes' => ['username' => 'user15']],
                    ['type' => 'users', 'id' => '<toString(@user16->id)>', 'attributes' => ['username' => 'user16']],
                ],
                'links' => [
                    'self'  => $url,
                    'first' => $urlWithFilter . '&page%5Bsize%5D=2&sort=username',
                    'prev'  => $urlWithFilter . '&page%5Bnumber%5D=2&page%5Bsize%5D=2&sort=username',
                    'next'  => $urlWithFilter . '&page%5Bnumber%5D=4&page%5Bsize%5D=2&sort=username',
                ]
            ],
            $response
        );
    }

    public function testTryToUseSearchTextFilterWithAnotherFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter' => ['searchText' => 'smith', 'username' => 'user1']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search filter cannot be used together with other filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToUseSearchTextFilterWithSortingByFieldUndefinedInSearchIndex(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchText]' => 'smith', 'sort' => 'googleId'],
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
            ['filter[searchText]' => 'smith', 'sort' => 'username', 'fields[users]' => 'username'],
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

    public function testSearchQueryFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchQuery]' => 'lastName = Smith', 'sort' => 'username', 'fields[users]' => 'username'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user11->id)>', 'attributes' => ['username' => 'user11']],
                    ['type' => 'users', 'id' => '<toString(@user12->id)>', 'attributes' => ['username' => 'user12']],
                    ['type' => 'users', 'id' => '<toString(@user13->id)>', 'attributes' => ['username' => 'user13']],
                    ['type' => 'users', 'id' => '<toString(@user14->id)>', 'attributes' => ['username' => 'user14']],
                    ['type' => 'users', 'id' => '<toString(@user15->id)>', 'attributes' => ['username' => 'user15']],
                    ['type' => 'users', 'id' => '<toString(@user16->id)>', 'attributes' => ['username' => 'user16']],
                    ['type' => 'users', 'id' => '<toString(@user17->id)>', 'attributes' => ['username' => 'user17']],
                    ['type' => 'users', 'id' => '<toString(@user18->id)>', 'attributes' => ['username' => 'user18']],
                    ['type' => 'users', 'id' => '<toString(@user19->id)>', 'attributes' => ['username' => 'user19']],
                    ['type' => 'users', 'id' => '<toString(@user21->id)>', 'attributes' => ['username' => 'user21']],
                ]
            ],
            $response
        );
        self::assertEquals(15, $response->headers->get('X-Include-Total-Count'));
    }

    public function testSearchQueryAndSearchTextFilters(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            [
                'filter[searchText]' => 'Smith',
                'filter[searchQuery]' => 'firstName = Robert',
                'sort' => 'username',
                'fields[users]' => 'username'
            ],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user11->id)>', 'attributes' => ['username' => 'user11']],
                    ['type' => 'users', 'id' => '<toString(@user12->id)>', 'attributes' => ['username' => 'user12']],
                    ['type' => 'users', 'id' => '<toString(@user13->id)>', 'attributes' => ['username' => 'user13']],
                    ['type' => 'users', 'id' => '<toString(@user14->id)>', 'attributes' => ['username' => 'user14']],
                    ['type' => 'users', 'id' => '<toString(@user15->id)>', 'attributes' => ['username' => 'user15']],
                    ['type' => 'users', 'id' => '<toString(@user16->id)>', 'attributes' => ['username' => 'user16']],
                    ['type' => 'users', 'id' => '<toString(@user17->id)>', 'attributes' => ['username' => 'user17']],
                    ['type' => 'users', 'id' => '<toString(@user18->id)>', 'attributes' => ['username' => 'user18']],
                    ['type' => 'users', 'id' => '<toString(@user19->id)>', 'attributes' => ['username' => 'user19']],
                ]
            ],
            $response
        );
        self::assertEquals(9, $response->headers->get('X-Include-Total-Count'));
    }

    public function testSearchQueryAndSearchTextFiltersWithEmptyResult(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            [
                'filter[searchText]' => 'Smith',
                'filter[searchQuery]' => 'firstName = John',
                'sort' => 'username',
                'fields[users]' => 'username'
            ],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains(['data' => []], $response);
        self::assertEquals(0, $response->headers->get('X-Include-Total-Count'));
    }

    public function testSearchTextFilterAndAggregation(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            [
                'filter[searchText]' => 'smith',
                'filter[aggregations]' => 'lastName count,firstName count',
                'sort' => 'username',
                'fields[users]' => 'username'
            ],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user11->id)>', 'attributes' => ['username' => 'user11']],
                    ['type' => 'users', 'id' => '<toString(@user12->id)>', 'attributes' => ['username' => 'user12']],
                    ['type' => 'users', 'id' => '<toString(@user13->id)>', 'attributes' => ['username' => 'user13']],
                    ['type' => 'users', 'id' => '<toString(@user14->id)>', 'attributes' => ['username' => 'user14']],
                    ['type' => 'users', 'id' => '<toString(@user15->id)>', 'attributes' => ['username' => 'user15']],
                    ['type' => 'users', 'id' => '<toString(@user16->id)>', 'attributes' => ['username' => 'user16']],
                    ['type' => 'users', 'id' => '<toString(@user17->id)>', 'attributes' => ['username' => 'user17']],
                    ['type' => 'users', 'id' => '<toString(@user18->id)>', 'attributes' => ['username' => 'user18']],
                    ['type' => 'users', 'id' => '<toString(@user19->id)>', 'attributes' => ['username' => 'user19']],
                    ['type' => 'users', 'id' => '<toString(@user21->id)>', 'attributes' => ['username' => 'user21']],
                ],
                'meta' => [
                    'aggregatedData' => [
                        'lastNameCount' => [
                            ['value' => 'Smith', 'count' => 15]
                        ],
                        'firstNameCount' => [
                            ['value' => 'Robert', 'count' => 9],
                            ['value' => 'Janet', 'count' => 6]
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertEquals(15, $response->headers->get('X-Include-Total-Count'));
    }

    public function testAggregationOnly(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            [
                'filter[aggregations]' => 'organization min firstOrganization',
                'sort' => 'username',
                'fields[users]' => 'username'
            ],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@user->id)>', 'attributes' => ['username' => 'admin']],
                    ['type' => 'users', 'id' => '<toString(@user11->id)>', 'attributes' => ['username' => 'user11']],
                    ['type' => 'users', 'id' => '<toString(@user12->id)>', 'attributes' => ['username' => 'user12']],
                    ['type' => 'users', 'id' => '<toString(@user13->id)>', 'attributes' => ['username' => 'user13']],
                    ['type' => 'users', 'id' => '<toString(@user14->id)>', 'attributes' => ['username' => 'user14']],
                    ['type' => 'users', 'id' => '<toString(@user15->id)>', 'attributes' => ['username' => 'user15']],
                    ['type' => 'users', 'id' => '<toString(@user16->id)>', 'attributes' => ['username' => 'user16']],
                    ['type' => 'users', 'id' => '<toString(@user17->id)>', 'attributes' => ['username' => 'user17']],
                    ['type' => 'users', 'id' => '<toString(@user18->id)>', 'attributes' => ['username' => 'user18']],
                    ['type' => 'users', 'id' => '<toString(@user19->id)>', 'attributes' => ['username' => 'user19']],
                ],
                'meta' => [
                    'aggregatedData' => [
                        'firstOrganization' => $this->getReference('organization')->getId()
                    ]
                ]
            ],
            $response
        );
        self::assertEquals(16, $response->headers->get('X-Include-Total-Count'));
    }

    /**
     * @dataProvider invalidSearchQueryDataProvider
     */
    public function testTryToSearchByInvalidSearchQuery(string $searchQuery, string $errorMessage): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[searchQuery]' => $searchQuery],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => $errorMessage,
                'source' => ['parameter' => 'filter[searchQuery]']
            ],
            $response
        );
    }

    public static function invalidSearchQueryDataProvider(): array
    {
        return [
            [
                'lastName : Doe',
                'Not allowed operator. Unexpected token "punctuation" of value ":"'
                . ' ("operator" expected with value "~, !~, =, !=, in, !in, starts_with,'
                . ' exists, notexists, like, notlike") around position 10.'
            ],
            [
                'not_existing_field = Doe',
                'The field "not_existing_field" is not supported.'
            ],
            [
                'organization = a',
                'Invalid search query.'
            ],
            [
                'organization = 1a',
                'Unexpected string "a" in where statement around position 17.'
            ],
        ];
    }

    public function testTryToSearchWithInvalidAggregation(): void
    {
        $response = $this->cget(
            ['entity' => 'users'],
            ['filter[aggregations]' => 'organization unknown_function'],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The aggregating function "unknown_function" is not supported.',
                'source' => ['parameter' => 'filter[aggregations]']
            ],
            $response
        );
    }
}
