<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserACLData;

class UserControllerACLTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadUserACLData::class]);
    }

    /**
     * @dataProvider ACLProvider
     *
     * @param string $resource
     * @param string $user
     * @param int $status
     */
    public function testACLInfoAction($resource, $user, $status)
    {
        $this->loginUser($user);
        /* @var $resource User */
        $resource = $this->getReference($resource);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_user_widget_info',
                ['id' => $resource->getId(), '_widgetContainer' => 'block']
            )
        );

        $response = $this->client->getResponse();
        static::assertHtmlResponseStatusCodeEquals($response, $status);
    }

    /**
     * @return array
     */
    public function ACLProvider()
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
