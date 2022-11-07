<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserACLData;

class UserControllerACLTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserACLData::class]);
    }

    /**
     * @dataProvider aclProvider
     */
    public function testACLInfoAction(string $resource, string $user, int $status)
    {
        $this->loginUser($user);
        /* @var User $resource */
        $resource = $this->getReference($resource);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_user_widget_info',
                ['id' => $resource->getId(), '_widgetContainer' => 'block']
            )
        );

        $response = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($response, $status);
    }

    public function aclProvider(): array
    {
        return [
            'VIEW (user from same business unit: VIEW_LOCAL)' => [
                'resource' => LoadUserACLData::SIMPLE_USER_ROLE_LOCAL,
                'user' => LoadUserACLData::SIMPLE_USER_2_ROLE_LOCAL,
                'status' => 403,
            ],
            'VIEW (user from from same business unit: VIEW_SYSTEM)' => [
                'resource' => LoadUserACLData::SIMPLE_USER_ROLE_LOCAL,
                'user' => LoadUserACLData::SIMPLE_USER_ROLE_SYSTEM,
                'status' => 200,
            ],
            'VIEW (user from another business unit : VIEW_LOCAL)' => [
                'resource' => LoadUserACLData::SIMPLE_USER_2_ROLE_LOCAL_BU2,
                'user' => LoadUserACLData::SIMPLE_USER_ROLE_LOCAL,
                'status' => 403,
            ]
        ];
    }
}
