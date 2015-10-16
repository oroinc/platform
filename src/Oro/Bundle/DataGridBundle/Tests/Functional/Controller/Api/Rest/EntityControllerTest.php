<?php

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @dbIsolation
 */
class EntityControllerTest extends WebTestCase
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
            $this->getUrl('oro_datagrid_api_rest_entity_patch', [
                'className' => $className,
                'id' => $id
            ]),
            [],
            [],
            [],
            $content
        );

        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());
    }
}
