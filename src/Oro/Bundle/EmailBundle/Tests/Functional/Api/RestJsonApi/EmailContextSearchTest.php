<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\JsonApiDocContainsConstraint;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailActivityData;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailSuggestionData;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;

/**
 * @group search
 */
class EmailContextSearchTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadEmailActivityData::class, LoadEmailSuggestionData::class, LoadUser::class]);
        // do the reindex because by some unknown reasons the search index is empty
        // after upgrade from old application version
        $indexer = self::getContainer()->get('oro_search.search.engine.indexer');
        $indexer->reindex(User::class);
    }

    private static function filterResponseContent(Response $response): array
    {
        $entityTypes = ['users'];
        $responseContent = self::jsonToArray($response->getContent());
        $filteredResponseContent = ['data' => []];
        foreach ($responseContent['data'] as $item) {
            $entityType = $item['relationships']['entity']['data']['type'];
            if (in_array($entityType, $entityTypes, true)) {
                $filteredResponseContent['data'][] = $item;
            }
        }
        if (isset($responseContent['included'])) {
            $filteredResponseContent['included'] = [];
            foreach ($responseContent['included'] as $item) {
                $entityType = $item['type'];
                if (in_array($entityType, $entityTypes, true)) {
                    $filteredResponseContent['included'][] = $item;
                }
            }
        }

        return $filteredResponseContent;
    }

    private static function assertResponseContent(array $expectedContent, array $content): void
    {
        try {
            self::assertThat($content, new JsonApiDocContainsConstraint($expectedContent, false, false));
        } catch (ExpectationFailedException $e) {
            // add the response data to simplify finding an error when a test is failed
            throw new ExpectationFailedException($e->getMessage() . "\nResponse Data:\n" . Yaml::dump($content, 8));
        }
    }

    private function getUser(string $reference): User
    {
        return $this->getReference($reference);
    }

    private function getUserUrl(int $userId): string
    {
        return $this->getUrl('oro_user_view', ['id' => $userId], true);
    }

    private function getUserData(string $reference): array
    {
        $user = $this->getUser($reference);

        return [
            'type'          => 'emailcontextsearch',
            'id'            => 'users-' . $user->getId(),
            'links'         => [
                'entityUrl' => $this->getUserUrl($user->getId())
            ],
            'attributes'    => [
                'entityName' => trim($user->getFirstName() . ' ' . $user->getLastName())
            ],
            'relationships' => [
                'entity' => ['data' => ['type' => 'users', 'id' => (string)$user->getId()]]
            ]
        ];
    }

    private function getUserIncludeData(string $reference): array
    {
        $user = $this->getUser($reference);

        return [
            'type'       => 'users',
            'id'         => (string)$user->getId(),
            'attributes' => [
                'username' => $user->getUserIdentifier()
            ]
        ];
    }

    public function testSearchWithoutSearchText(): void
    {
        $response = $this->cget(['entity' => 'emailcontextsearch'], ['page[size]' => -1]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_11'),
                $this->getUserData('user_10'),
                $this->getUserData('user_5'),
                $this->getUserData('user_4'),
                $this->getUserData('user_3'),
                $this->getUserData('user_2'),
                $this->getUserData('user_1'),
                $this->getUserData('simple_user2'),
                $this->getUserData('simple_user'),
                $this->getUserData('user')
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchBySpecifiedEntityTypes(): void
    {
        $response = $this->cget(['entity' => 'emailcontextsearch'], ['filter' => ['entities' => 'users']]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_11'),
                $this->getUserData('user_10'),
                $this->getUserData('user_5'),
                $this->getUserData('user_4'),
                $this->getUserData('user_3'),
                $this->getUserData('user_2'),
                $this->getUserData('user_1'),
                $this->getUserData('simple_user2'),
                $this->getUserData('simple_user'),
                $this->getUserData('user')
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchBySearchText(): void
    {
        $response = $this->cget(['entity' => 'emailcontextsearch'], ['filter' => ['searchText' => 'Doe']]);
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user')
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchWithInclude(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontextsearch'],
            ['include' => 'entity', 'filter' => ['entities' => 'users']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data'     => [
                $this->getUserData('user_11'),
                $this->getUserData('user_10'),
                $this->getUserData('user_5'),
                $this->getUserData('user_4'),
                $this->getUserData('user_3'),
                $this->getUserData('user_2'),
                $this->getUserData('user_1'),
                $this->getUserData('simple_user2'),
                $this->getUserData('simple_user'),
                $this->getUserData('user')
            ],
            'included' => [
                $this->getUserIncludeData('user_11'),
                $this->getUserIncludeData('user_10'),
                $this->getUserIncludeData('user_5'),
                $this->getUserIncludeData('user_4'),
                $this->getUserIncludeData('user_3'),
                $this->getUserIncludeData('user_2'),
                $this->getUserIncludeData('user_1'),
                $this->getUserIncludeData('simple_user2'),
                $this->getUserIncludeData('simple_user'),
                $this->getUserIncludeData('user')
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'emailcontextsearch', 'id' => 'users-1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'emailcontextsearch'],
            ['data' => ['type' => 'emailcontextsearch', 'id' => 'users-1']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'emailcontextsearch', 'id' => 'users-1'],
            ['data' => ['type' => 'emailcontextsearch', 'id' => 'users-1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'emailcontextsearch', 'id' => 'users-1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'emailcontextsearch'],
            ['filter' => ['id' => 'users-1']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
