<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\Controller\Api;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RestApiTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures\LoadCommentData']);
    }

    public function testCget()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_comment_get_items',
                [
                    'relationClass' => 'Oro_Bundle_EmailBundle_Entity_Email',
                    'relationId'    => $this->getReference('default_activity')->getId()
                ]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(3, $result['count']);
        $this->assertCount(3, $result['data']);

        $actualMessages = [];
        foreach ($result['data'] as $comment) {
            $this->assertArrayHasKey('message', $comment);
            $actualMessages[] = $comment['message'];
        }
        $this->assertContains('First comment', $actualMessages);
        $this->assertContains('Second comment', $actualMessages);
        $this->assertContains('Third comment', $actualMessages);
    }

    public function testCgetCreatedDateFiltering()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_comment_get_items',
                [
                    'relationClass' => 'Oro_Bundle_EmailBundle_Entity_Email',
                    'relationId'    => $this->getReference('default_activity')->getId()
                ]
            ) . '?createdAt<' . urlencode($date->format('c'))
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(2, $result['count']);
        $this->assertCount(2, $result['data']);
    }

    public function testCgetUpdatedDateFiltering()
    {
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_comment_get_items',
                [
                    'relationClass' => 'Oro_Bundle_EmailBundle_Entity_Email',
                    'relationId'    => $this->getReference('default_activity')->getId()
                ]
            ) . '?updatedAt>' . urlencode($date->format('c'))
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(1, $result['count']);
        $this->assertCount(1, $result['data']);
    }

    public function testPost()
    {
        $request = [
            'message'       => 'test message',
            'id'            => 0,
            'owner'         => '',
            'owner_id'      => '',
            'editor'        => '',
            'editor_id'     => '',
            'relationClass' => '',
            'relationId'    => '',
            'createdAt'     => '',
            'updatedAt'     => '',
            'editable'      => '',
            'removable'     => '',
        ];

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_api_comment_create_item',
                [
                    'relationClass' => 'Oro_Bundle_EmailBundle_Entity_Email',
                    'relationId'    => $this->getReference('default_activity')->getId()
                ]
            ),
            $request
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $this->assertTrue(isset($result['relationClass']));
        $this->assertTrue(isset($result['relationId']));
        $this->assertTrue(isset($result['updatedAt']));

        return $result;
    }


    /**
     * @depends testPost
     *
     * @param array $request
     *
     * @return array $request
     */
    public function testPut($request)
    {
        $request['message'] = 'new message';

        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_api_comment_update_item',
                [
                    'id' => $request['id']
                ]
            ),
            $request
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $this->assertTrue(isset($result['message']));
        $this->assertEquals('new message', $result['message']);

        return $result['id'];
    }

    /**
     * @depends testPut
     *
     * @param int $id
     */
    public function testGet($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_comment_get_item', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);

        return $result['id'];
    }

    /**
     * @depends testGet
     *
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl(
                'oro_api_comment_delete_item',
                ['id' => $id]
            ),
            [],
            [],
            $this->generateWsseAuthHeader()
        );

        $response = $this->client->getResponse();
        $result   = $this->assertEmptyResponseStatusCodeEquals($response, 204);

        $this->assertEmpty($result);
    }
}
