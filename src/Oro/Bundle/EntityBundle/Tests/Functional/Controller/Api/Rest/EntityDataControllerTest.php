<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller\Api\Rest;

use FOS\RestBundle\Util\Codes;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class EntityDataControllerTest extends WebTestCase
{
    public function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures([
            'Oro\Bundle\DataGridBundle\Tests\Functional\DataFixtures\LoadUserData',
        ]);
    }

    public function testChangeSimpleField()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $className = 'Oro_Bundle_UserBundle_Entity_User';
        $id = $user->getId();
        $content = '{"firstName":"Test"}';
        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => $className,
                'id' => $id
            ]),
            [],
            [],
            [],
            $content
        );

        $this->assertEquals(Codes::HTTP_NO_CONTENT, $this->client->getResponse()->getStatusCode());
    }

    public function testChangeSimpleFieldFromBlackList()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $className = 'Oro_Bundle_UserBundle_Entity_User';
        $id = $user->getId();
        $content = '{"id":10}';
        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => $className,
                'id' => $id
            ]),
            [],
            [],
            [],
            $content
        );

        $this->assertEquals(Codes::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }

    public function testNotFoundEntity()
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        $className = 'Oro_Test_Entity';
        $id = $user->getId();
        $content = '{"firstName":"Test"}';
        $this->client->request(
            'PATCH',
            $this->getUrl('oro_api_patch_entity_data', [
                'className' => $className,
                'id' => $id
            ]),
            [],
            [],
            [],
            $content
        );

        $this->assertEquals(Codes::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }
}
