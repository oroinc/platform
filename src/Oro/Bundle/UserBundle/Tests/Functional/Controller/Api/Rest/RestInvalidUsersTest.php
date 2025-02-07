<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestInvalidUsersTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    /**
     * @dataProvider usernameKeyDataProvider
     */
    public function testInvalidCredentials(string $username, ?int $organizationId): void
    {
        $request = [
            'user' => [
                'username' => 'user_' . mt_rand(),
                'email' => 'test_' . mt_rand() . '@test.com',
                'enabled' => 'true',
                'plainPassword' => '1231231q',
                'firstName' => 'firstName',
                'lastName' => 'lastName',
                'userRoles' => ['1'],
            ],
        ];
        $this->client->jsonRequest(
            'POST',
            $this->getUrl('oro_api_post_user'),
            $request,
            self::generateApiAuthHeader($username, $organizationId)
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 401);
    }

    public function usernameKeyDataProvider(): array
    {
        return [
            'invalid key' => [self::USER_NAME, 55],
            'invalid user' => ['invalid_user', null],
        ];
    }
}
