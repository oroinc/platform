<?php

namespace Oro\Bundle\TagBundle\Tests\Functional\Controller;

use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TagBundle\Entity\Tag;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class DictionaryControllerAclTest extends WebTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            '@OroTagBundle/Tests/Functional/DataFixtures/DifferentOwnerTags.yml',
            LoadUserData::class
        ]);
    }

    public function testGetItemsForDefaultAclPermissions()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_dictionary_search', ['dictionary' => str_replace('\\', '_', Tag::class)])
        );
        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(5, $responseData['results']);
        self::assertEquals('admin_tag', $responseData['results'][0]['text']);
        self::assertEquals('user_business_unit_tag', $responseData['results'][1]['text']);
        self::assertEquals('user_bu_main_child_tag', $responseData['results'][2]['text']);
        self::assertEquals('user_bu_first_tag', $responseData['results'][3]['text']);
        self::assertEquals('user_bu_first_child_tag', $responseData['results'][4]['text']);
    }

    public function testGetItemsForDivisionAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            Tag::class,
            [
                'VIEW'   => AccessLevel::DEEP_LEVEL,
                'EDIT'   => AccessLevel::DEEP_LEVEL,
                'ASSIGN' => AccessLevel::DEEP_LEVEL,
                'CREATE' => AccessLevel::LOCAL_LEVEL
            ]
        );

        $this->client->request(
            'GET',
            $this->getUrl('oro_dictionary_search', ['dictionary' => str_replace('\\', '_', Tag::class)])
        );
        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(3, $responseData['results']);
        self::assertEquals('admin_tag', $responseData['results'][0]['text']);
        self::assertEquals('user_business_unit_tag', $responseData['results'][1]['text']);
        self::assertEquals('user_bu_main_child_tag', $responseData['results'][2]['text']);
    }

    public function testGetItemsForBusinessUnitAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            Tag::class,
            [
                'VIEW'   => AccessLevel::LOCAL_LEVEL,
                'EDIT'   => AccessLevel::DEEP_LEVEL,
                'ASSIGN' => AccessLevel::DEEP_LEVEL,
                'CREATE' => AccessLevel::LOCAL_LEVEL
            ]
        );

        $this->client->request(
            'GET',
            $this->getUrl('oro_dictionary_search', ['dictionary' => str_replace('\\', '_', Tag::class)])
        );
        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(2, $responseData['results']);
        self::assertEquals('admin_tag', $responseData['results'][0]['text']);
        self::assertEquals('user_business_unit_tag', $responseData['results'][1]['text']);
    }

    public function testGetItemsForUserAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            Tag::class,
            [
                'VIEW'   => AccessLevel::BASIC_LEVEL,
                'EDIT'   => AccessLevel::DEEP_LEVEL,
                'ASSIGN' => AccessLevel::DEEP_LEVEL,
                'CREATE' => AccessLevel::LOCAL_LEVEL
            ]
        );

        $this->client->request(
            'GET',
            $this->getUrl('oro_dictionary_search', ['dictionary' => str_replace('\\', '_', Tag::class)])
        );
        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $responseData['results']);
        self::assertEquals('admin_tag', $responseData['results'][0]['text']);
    }

    public function testGetItemsForNoneAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            Tag::class,
            [
                'VIEW'   => AccessLevel::NONE_LEVEL,
                'EDIT'   => AccessLevel::DEEP_LEVEL,
                'ASSIGN' => AccessLevel::DEEP_LEVEL,
                'CREATE' => AccessLevel::LOCAL_LEVEL
            ]
        );

        $this->client->request(
            'GET',
            $this->getUrl('oro_dictionary_search', ['dictionary' => str_replace('\\', '_', Tag::class)])
        );
        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(0, $responseData['results']);
    }
}
