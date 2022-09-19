<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Acl;

use Oro\Bundle\ApiBundle\Tests\Functional\JsonApiDocContainsConstraint;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadAllRolesData;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Yaml\Yaml;

class AnonymousRoleAccessTest extends RestJsonApiTestCase
{
    use SearchExtensionTrait;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadAllRolesData::class]);

        self::resetIndex();
        self::reindex(Role::class);
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

    public function testAccessToViewCommonRole(): void
    {
        $role = $this->getReference('role.role_administrator');

        $this->client->request('GET', $this->getUrl('oro_user_role_view', ['id' => $role->getId()]));
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
    }

    public function testTryToAccessTheAnonymousRole(): void
    {
        $role = $this->getReference('role.is_authenticated_anonymously');

        $this->client->request('GET', $this->getUrl('oro_user_role_view', ['id' => $role->getId()]));
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 403);
    }

    public function testSearchCommonRole(): void
    {
        $role = $this->getReference('role.role_administrator');

        $response = $this->cget(
            ['entity' => 'search'],
            ['filter' => ['entities' => 'userroles', 'searchText' => $role->getLabel()]]
        );

        $expectedContent = [
            'data' => [
                [
                    'type' => 'search',
                    'id'   => 'userroles-' . $role->getId(),
                ]
            ]
        ];
        self::assertResponseContent($expectedContent, self::jsonToArray($response->getContent()));
    }

    public function testTryToSearchTheAnonymousRole(): void
    {
        $role = $this->getReference('role.is_authenticated_anonymously');

        $response = $this->cget(
            ['entity' => 'search'],
            ['filter' => ['entities' => 'userroles', 'searchText' => $role->getLabel()]]
        );

        self::assertCount(0, self::jsonToArray($response->getContent())['data']);
    }
}
