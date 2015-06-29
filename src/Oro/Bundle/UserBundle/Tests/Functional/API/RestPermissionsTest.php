<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\API\DataFixtures\LoadUserData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestPermissionsTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var User
     */
    protected $user;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\UserBundle\Tests\Functional\API\DataFixtures\LoadUserData']);
        $this->em   = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->user = $this->em->getRepository('OroUserBundle:User')->findOneBy([
            'username' => LoadUserData::USER_NAME
        ]);
    }

    public function testGetPermissions()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_user_permissions', ['id' => $this->user->getId()])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result, "Permissions should not be empty");
    }

    public function testGetPermissionsWithEntities()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_user_permissions', [
                'id'       => $this->user->getId(),
                'entities' => 'Oro\Bundle\UserBundle\Entity\User'
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(1, $result, "Result should contains only permissions for one entity");

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_user_permissions', [
                'id'       => $this->user->getId(),
                'entities' => implode(
                    ',',
                    [
                        'Oro\Bundle\UserBundle\Entity\User',
                        'Oro\Bundle\OrganizationBundle\Entity\Organization'
                    ]
                )
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $result, "Result should contains only permissions for two entities");

    }
}
