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
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmailContextTest extends RestJsonApiTestCase
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

    private static function assertResponseContent(
        array $expectedContent,
        array $content,
        bool $strictOrder = false
    ): void {
        try {
            self::assertThat($content, new JsonApiDocContainsConstraint($expectedContent, false, $strictOrder));
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

    private function getUserData(string $reference, bool $isContext): array
    {
        $user = $this->getUser($reference);

        return [
            'type'          => 'emailcontext',
            'id'            => 'users-' . $user->getId(),
            'links'         => ['entityUrl' => $this->getUserUrl($user->getId())],
            'attributes'    => [
                'entityName' => trim($user->getFirstName() . ' ' . $user->getLastName()),
                'isContext'  => $isContext
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
                'username' => $user->getUsername()
            ]
        ];
    }

    public function testSearchWithMessageIdForExistingEmail(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter[messageId]' => 'email1@orocrm-pro.func-test', 'page[size]' => -1]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_5', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent, true);
    }

    public function testSearchWithMessageIdForNotExistingEmail(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter[messageId]' => 'unknown@example.com']
        );
        self::assertResponseCount(0, $response);
    }

    public function testSearchWithMessageIdForExistingEmailAndWithFromToAndCc(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId' => 'email1@orocrm-pro.func-test',
                    'from'      => 'email1@orocrm-pro.func-test',
                    'to'        => 'richard_bradley@example.com',
                    'cc'        => ['brenda_brock@example.com', 'lucas_thornton@example.com']
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_5', false),
                $this->getUserData('user_4', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchWithMessageIdForNotExistingEmailAndWithFromToAndCc(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId' => 'unknown@example.com',
                    'from'      => 'email1@orocrm-pro.func-test',
                    'to'        => 'richard_bradley@example.com',
                    'cc'        => ['brenda_brock@example.com', 'lucas_thornton@example.com']
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_4', false),
                $this->getUserData('user_2', false),
                $this->getUserData('user_1', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchBySpecifiedEntityTypes(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'entities' => 'users']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_5', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchBySearchText(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Doe']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchWithInclude(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['include' => 'entity', 'filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'entities' => 'users']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data'     => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_5', false)
            ],
            'included' => [
                $this->getUserIncludeData('user_1'),
                $this->getUserIncludeData('user_2'),
                $this->getUserIncludeData('user_3'),
                $this->getUserIncludeData('user_5')
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchWithIsContextFalse(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'isContext' => 'no']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_5', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchWithIsContextTrue(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'isContext' => 'yes']]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchWithExcludeCurrentUserFalse(): void
    {
        $currentUser = $this->getUser('user');
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId'          => 'unknown@example.com',
                    'excludeCurrentUser' => 'no',
                    'from'               => 'email1@orocrm-pro.func-test',
                    'to'                 => ['richard_bradley@example.com', $currentUser->getEmail()]
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', false),
                $this->getUserData('user', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchWithExcludeCurrentUserTrue(): void
    {
        $currentUser = $this->getUser('user');
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId'          => 'unknown@example.com',
                    'excludeCurrentUser' => 'yes',
                    'from'               => 'email1@orocrm-pro.func-test',
                    'to'                 => ['richard_bradley@example.com', $currentUser->getEmail()]
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testSearchWithFromTonAndCc(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId' => 'email1@orocrm-pro.func-test'
                ]
            ]
        );
        $filteredResponseContent = self::filterResponseContent($response);
        $expectedContent = [
            'data' => [
                $this->getUserData('user_1', true),
                $this->getUserData('user_2', true),
                $this->getUserData('user_3', true),
                $this->getUserData('user_5', false)
            ]
        ];
        self::assertResponseContent($expectedContent, $filteredResponseContent);
    }

    public function testTryToSearchWithoutMessageId(): void
    {
        $response = $this->cget(['entity' => 'emailcontext'], [], [], false);
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The Message-ID is required.',
                'source' => ['parameter' => 'filter[messageId]']
            ],
            $response
        );
    }

    public function testTryToSearchBySearchTextAndFrom(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Doe', 'from' => 'a@a.com']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToSearchBySearchTextAndTo(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Doe', 'to' => 'a@a.com']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToSearchBySearchTextAndCc(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Doe', 'cc' => 'a@a.com']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToSearchBySearchTextAndIsContext(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            ['filter' => ['messageId' => 'email1@orocrm-pro.func-test', 'searchText' => 'Doe', 'isContext' => 'yes']],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }

    public function testTryToSearchBySearchTextAndExcludeCurrentUser(): void
    {
        $response = $this->cget(
            ['entity' => 'emailcontext'],
            [
                'filter' => [
                    'messageId'          => 'email1@orocrm-pro.func-test',
                    'searchText'         => 'Doe',
                    'excludeCurrentUser' => 'yes'
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'filter constraint',
                'detail' => 'The search text cannot be specified together with'
                    . ' "from", "to", "cc", "isContext" or "excludeCurrentUser" filters.',
                'source' => ['parameter' => 'filter[searchText]']
            ],
            $response
        );
    }
}
