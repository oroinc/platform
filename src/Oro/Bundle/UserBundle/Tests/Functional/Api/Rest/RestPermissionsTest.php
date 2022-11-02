<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\Api\DataFixtures\LoadUserData;

class RestPermissionsTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateWsseAuthHeader());
        $this->loadFixtures([LoadUserData::class]);
    }

    private function getUser(): User
    {
        return self::getContainer()->get('doctrine')->getRepository(User::class)
            ->findOneBy(['username' => LoadUserData::USER_NAME]);
    }

    public function testGetPermissions()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_user_permissions', ['id' => $this->getUser()->getId()])
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertNotEmpty($result, 'Permissions should not be empty');
    }

    public function testGetPermissionsWithEntities()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_user_permissions', [
                'id'       => $this->getUser()->getId(),
                'entities' => User::class
            ])
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertCount(1, $result, 'Result should contains only permissions for one entity');

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_user_permissions', [
                'id'       => $this->getUser()->getId(),
                'entities' => implode(',', ['user', Organization::class])
            ])
        );

        $result = self::getJsonResponseContent($this->client->getResponse(), 200);
        self::assertCount(2, $result, 'Result should contains only permissions for two entities');
    }
}
