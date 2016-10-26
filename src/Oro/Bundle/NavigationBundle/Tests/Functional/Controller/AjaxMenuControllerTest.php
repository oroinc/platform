<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Controller;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\NavigationBundle\Menu\Provider\GlobalOwnershipProvider;
use Oro\Bundle\NavigationBundle\Menu\Provider\UserOwnershipProvider;
use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\LoadMenuUpdateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AjaxMenuControllerTest extends WebTestCase
{
    const MENU_NAME = 'application_menu';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures([
            'Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\LoadMenuUpdateData'
        ]);
    }

    public function testCreateGlobal()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'parentKey' => LoadMenuUpdateData::MENU_UPDATE_1,
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_navigation_menuupdate_create', $parameters),
            [
                'ownerId' => 0,
                'isDivider' => true
            ]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_CREATED);
    }

    public function testCreateUser()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'parentKey' => 'menu_list_default',
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_navigation_menuupdate_create', $parameters),
            [
                'ownerId' => 1,
                'isDivider' => true
            ]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_CREATED);
    }

    public function testDeleteGlobal()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'key' => LoadMenuUpdateData::MENU_UPDATE_1_1,
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_navigation_menuupdate_delete', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testDeleteUser()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'key' => LoadMenuUpdateData::MENU_UPDATE_3_1,
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_navigation_menuupdate_delete', $parameters),
            ['ownerId' => $this->getReference('simple_user')->getId()]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testShowGlobal()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'key' => LoadMenuUpdateData::MENU_UPDATE_2_1,
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_show', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testShowUser()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'key' => LoadMenuUpdateData::MENU_UPDATE_3,
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_show', $parameters),
            ['ownerId' => $this->getReference('simple_user')->getId()]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testHideGlobal()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'key' => LoadMenuUpdateData::MENU_UPDATE_2,
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_hide', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testHideUser()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'key' => LoadMenuUpdateData::MENU_UPDATE_3,
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_hide', $parameters),
            ['ownerId' => $this->getReference('simple_user')->getId()]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testResetGlobal()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_navigation_menuupdate_reset', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testResetUser()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_navigation_menuupdate_reset', $parameters),
            ['ownerId' => $this->getReference('simple_user')->getId()]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testMoveGlobal()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'ownershipType' => GlobalOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_move', $parameters),
            [
                'ownerId' => 0,
                'key' => LoadMenuUpdateData::MENU_UPDATE_1,
                'parentKey' => self::MENU_NAME,
                'position' => 33
            ]
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testMoveUser()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'ownershipType' => UserOwnershipProvider::TYPE
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_menuupdate_move', $parameters),
            [
                'ownerId' => $this->getReference('simple_user')->getId(),
                'key' => LoadMenuUpdateData::MENU_UPDATE_3,
                'parentKey' => self::MENU_NAME,
                'position' => 11
            ]
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, Response::HTTP_OK);
    }
}
