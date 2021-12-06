<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class SegmentControllerTest extends WebTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures([
            '@OroSegmentBundle/Tests/Functional/DataFixtures/DifferentOwnerSegments.yml',
            LoadUserData::class
        ]);
    }

    public function testGetItemsForDefaultAclPermissions()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_segment_items', ['entityName' => str_replace('\\', '_', BusinessUnit::class)])
        );
        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(3, $responseData['results']);
        self::assertEquals('Child BU segment', $responseData['results'][0]['text']);
        self::assertEquals('Main BU segment', $responseData['results'][1]['text']);
        self::assertEquals('Second BU segment', $responseData['results'][2]['text']);
    }

    public function testGetItemsForDivisionAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            Segment::class,
            [
                'VIEW'   => AccessLevel::DEEP_LEVEL,
                'EDIT'   => AccessLevel::DEEP_LEVEL,
                'ASSIGN' => AccessLevel::DEEP_LEVEL,
                'CREATE' => AccessLevel::LOCAL_LEVEL
            ]
        );

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_segment_items', ['entityName' => str_replace('\\', '_', BusinessUnit::class)])
        );
        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(2, $responseData['results']);
        self::assertEquals('Child BU segment', $responseData['results'][0]['text']);
        self::assertEquals('Main BU segment', $responseData['results'][1]['text']);
    }

    public function testGetItemsForUserAccessLevel()
    {
        $this->updateRolePermissions(
            'ROLE_ADMINISTRATOR',
            Segment::class,
            [
                'VIEW'   => AccessLevel::LOCAL_LEVEL,
                'EDIT'   => AccessLevel::DEEP_LEVEL,
                'ASSIGN' => AccessLevel::DEEP_LEVEL,
                'CREATE' => AccessLevel::LOCAL_LEVEL
            ]
        );

        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_segment_items', ['entityName' => str_replace('\\', '_', BusinessUnit::class)])
        );
        $response = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertCount(1, $responseData['results']);
        self::assertEquals('Main BU segment', $responseData['results'][0]['text']);
    }
}
