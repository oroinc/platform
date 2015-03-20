<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\Controller\Api;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class RestApiTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures\LoadCommentData']);
    }

    /**
     * @return array
     */
    public function testCget()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_comment_get_items',
                [
                    'relationClass' => 'Oro_Bundle_CalendarBundle_Entity_CalendarEvent',
                    'relationId'    => 1
                ]
            )
        );

        $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    public function testCgetFiltering()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_comment_get_items',
                [
                    'relationClass' => 'Oro_Bundle_CalendarBundle_Entity_CalendarEvent',
                    'relationId'    => 1
                ]
            ) . '?createdAt<2014-03-04T20:00:00+0000'
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(0, $result['count']);
        $this->assertCount(0, $result['data']);
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
                    'relationClass' => 'Oro_Bundle_CalendarBundle_Entity_CalendarEvent',
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
