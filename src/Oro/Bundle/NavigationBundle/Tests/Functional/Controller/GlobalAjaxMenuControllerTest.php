<?php

namespace Oro\Bundle\NavigationBundle\Tests\Functional\Controller;

use Oro\Bundle\NavigationBundle\Tests\Functional\DataFixtures\MenuUpdateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GlobalAjaxMenuControllerTest extends WebTestCase
{
    const MENU_NAME = 'application_menu';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([MenuUpdateData::class]);
    }

    public function testCreate()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'parentKey' => MenuUpdateData::MENU_UPDATE_1,
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_navigation_global_menu_ajax_create', $parameters),
            [
                'isDivider' => true
            ]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_CREATED);
    }

    public function testDelete()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'key' => MenuUpdateData::MENU_UPDATE_1_1
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_navigation_global_menu_ajax_delete', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }

    public function testShow()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'key' => MenuUpdateData::MENU_UPDATE_2_1
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_global_menu_ajax_show', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testHide()
    {
        $parameters = [
            'menuName' => self::MENU_NAME,
            'key' => MenuUpdateData::MENU_UPDATE_2
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_global_menu_ajax_hide', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testMove()
    {
        $parameters = [
            'menuName' => self::MENU_NAME
        ];

        $this->client->request(
            'PUT',
            $this->getUrl('oro_navigation_global_menu_ajax_move', $parameters),
            [
                'ownerId' => 0,
                'key' => MenuUpdateData::MENU_UPDATE_1,
                'parentKey' => self::MENU_NAME,
                'position' => 33
            ]
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, Response::HTTP_OK);
    }

    public function testReset()
    {
        $parameters = [
            'menuName' => self::MENU_NAME
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_navigation_global_menu_ajax_reset', $parameters),
            ['ownerId' => 0]
        );

        $result = $this->client->getResponse();

        $this->assertResponseStatusCodeEquals($result, Response::HTTP_NO_CONTENT);
    }
}
