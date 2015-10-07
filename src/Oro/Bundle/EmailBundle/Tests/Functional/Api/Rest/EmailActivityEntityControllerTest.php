<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class EmailActivityEntityControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(
            [
                'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailActivityData'
            ]
        );
    }

    public function testGetEntities()
    {
        $url = $this->getUrl(
            'oro_api_get_activity_relations',
            ['activity' => 'emails', 'id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request('GET', $url);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(3, $entities);
    }

    public function testGetEntitiesWithPaging()
    {
        $url = $this->getUrl(
            'oro_api_get_activity_relations',
            ['activity' => 'emails', 'id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request(
            'GET',
            $url . '?page=2&limit=2',
            [],
            [],
            ['HTTP_X-Include' => 'totalCount']
        );
        $response = $this->client->getResponse();
        $entities = $this->getJsonResponseContent($response, 200);
        $this->assertCount(1, $entities);
        $this->assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    /**
     * @depends testGetEntities
     */
    public function testDeleteRelation()
    {
        $url = $this->getUrl(
            'oro_api_delete_activity_relation',
            [
                'activity' => 'emails',
                'id' => $this->getReference('email_1')->getId(),
                'entity' => 'users',
                'entityId' => $this->getReference('user_1')->getId()
            ]
        );
        $this->client->request('DELETE', $url);
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        // check that the relation was deleted
        $url = $this->getUrl(
            'oro_api_get_activity_relations',
            ['activity' => 'emails', 'id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request('GET', $url);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(2, $entities);
    }

    /**
     * @depends testDeleteRelation
     */
    public function testCreateRelation()
    {
        $url = $this->getUrl(
            'oro_api_post_activity_relation',
            ['activity' => 'emails', 'id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request(
            'POST',
            $url,
            [
                'targets' => [
                    ['entity' => 'user', 'id' => $this->getReference('user_1')->getId()]
                ]
            ]
        );
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        // check that the relation was added
        $url = $this->getUrl(
            'oro_api_get_activity_relations',
            ['activity' => 'emails', 'id' => $this->getReference('email_1')->getId()]
        );
        $this->client->request('GET', $url);
        $entities = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertCount(3, $entities);
    }

    public function testCreateEmailActivityRelation()
    {
        /** @var array */
        $email = [
            'folders'    => [
                ['fullName' => 'INBOX \ Test Folder', 'name' => 'Test Folder', 'type' => 'inbox']
            ],
            'subject'    => 'New does not exists email subject',
            'messageId'  => 'new.message.id@email-bundle.func-test',
            'from'       => '"Address 1" <emaildoesnotexists@example.com>',
            'to'         => ['"Address 2" <2@example.com>', '3@example.com'],
            'cc'         => '2@example.com; "Address 3" <3@example.com>',
            'importance' => 'low',
            'body'       => 'Test body',
            'bodyType'   => 'text',
            'receivedAt' => '2015-06-19 12:17:51'
        ];

        // Create new email
        $this->client->request('POST', $this->getUrl('oro_api_post_email'), $email);
        $response = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $this->assertArrayHasKey('id', $response);
        $emailId = $response['id'];

        // Create relation between email and user
        $user = $this->getReference('user_2');
        $this->client->request('POST', $this->getUrl('oro_api_post_activity_relation', [
            'activity' => 'emails',
            'id'       => $emailId
        ]), [
            'targets' => [
                [
                    'id'     => $user->getId(),
                    'entity' => 'user'
                ]
            ]
        ]);
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        // Init client for basic authentication
        $this->initClient([], $this->generateBasicAuthHeader());

        // Check that user has activity list target(email) on view page
        $this->client->request('GET', $this->getUrl('oro_user_view', ['id' => $user->getId()]));
        $content = $this->client->getResponse()->getContent();
        $this->assertContains($email['subject'], $content);
    }
}
