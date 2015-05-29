<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailControllerTest extends WebTestCase
{
    const INCORRECT_ID = -1;

    /** @var array */
    protected $email = [
        'folders'    => [
            ['fullName' => 'INBOX \ Test Folder', 'name' => 'Test Folder', 'type' => 'inbox']
        ],
        'subject'    => 'New email',
        'messageId'  => 'test@email-bundle.func-test',
        'from'       => '"Address 1" <1@example.com>',
        'to'         => ['"Address 2" <2@example.com>', '3@example.com'],
        'cc'         => '2@example.com; "Address 3" <3@example.com>',
        'importance' => 'low',
        'body'       => 'Test body',
        'bodyType'   => 'text'
    ];

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData']);
    }

    /**
     * @return array
     */
    public function testCget()
    {
        $url = $this->getUrl('oro_api_get_emails');
        $this->client->request('GET', $url);

        $emails = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($emails);
        $this->assertCount(10, $emails);

        $this->client->request('GET', $url . '?messageId=' . $emails[0]['messageId']);
        $this->assertCount(1, $this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->client->request('GET', $url . '?messageId<>' . $emails[0]['messageId']);
        $this->assertCount(9, $this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->client->request('GET', $url . '?messageId=' . $emails[0]['messageId'] . ',' . $emails[5]['messageId']);
        $this->assertCount(2, $this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->client->request('GET', $url . '?messageId<>' . $emails[0]['messageId'] . ',' . $emails[5]['messageId']);
        $this->assertCount(8, $this->getJsonResponseContent($this->client->getResponse(), 200));
    }

    public function testGet()
    {
        $id = $this->getReference('email_1')->getId();
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_email', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
        $this->assertEquals('My Web Store Introduction', $result['subject']);
        $this->assertContains('Thank you for signing up to My Web Store!', $result['body']);

        return $result['id'];
    }

    public function testGetAssociation()
    {
        $this->getAssociation(self::INCORRECT_ID);
        $this->getJsonResponseContent($this->client->getResponse(), 404);

        $id = $this->getReference('email_1')->getId();
        $this->getAssociation($id);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);


        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
    }

    public function testDeleteAssociation()
    {
        $userId = $this->getReference('simple_user2')->getId();
        $this->deleteAssociation(self::INCORRECT_ID, 'Oro\Bundle\UserBundle\Entity\User', $userId);
        $this->getJsonResponseContent($this->client->getResponse(), 404);

        $id     = $this->getReference('email_1')->getId();
        $userId = $this->getReference('simple_user2')->getId();
        $this->deleteAssociation($id, 'Oro\Bundle\UserBundle\Entity\User', $userId);
        $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    public function testPostAssociation()
    {
        $userId = $this->getReference('simple_user2')->getId();
        $this->postAssociation(self::INCORRECT_ID, 'Oro\Bundle\UserBundle\Entity\User', $userId);
        $this->getJsonResponseContent($this->client->getResponse(), 404);

        $id     = $this->getReference('email_1')->getId();
        $userId = $this->getReference('simple_user2')->getId();

        $this->getAssociation($id);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);

        $this->postAssociation($id, 'Oro\Bundle\UserBundle\Entity\User', $userId);
        $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->getAssociation($id);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
    }


    protected function getAssociationData($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_email_associations_data', ['entityId' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        return $result;
    }


    protected function getAssociation($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_email_association', ['entityId' => $id])
        );
    }

    protected function deleteAssociation($entityId, $targetClassName, $targetId)
    {
        $param = [
            'entityId'        => $entityId,
            'targetClassName' => $targetClassName,
            'targetId'        => $targetId
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_email_association', $param)
        );
    }

    protected function postAssociation($entityId, $targetClassName, $targetId)
    {
        $param = [
            'entityId'        => $entityId,
            'targetClassName' => $targetClassName,
            'targetId'        => $targetId
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_email_associations', $param)
        );
    }

    public function testCreateEmail()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_email'), $this->email);
        $response = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->client->request('GET', $this->getUrl('oro_api_get_email', ['id' => $response['id']]));
        $email = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertTrue($email['head']);
        $this->assertFalse($email['seen']);

        return $response['id'];
    }

    /**
     * @depends testCreateEmail
     *
     * @param integer $id
     */
    public function testCreateForExistingEmail($id)
    {
        $newEmail = array_merge($this->email, ['subject' => 'New subject']);
        $this->client->request('POST', $this->getUrl('oro_api_post_email'), $newEmail);
        $response = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertEquals($response['id'], $id, 'Existing email should be updated');

        $this->client->request('GET', $this->getUrl('oro_api_get_email', ['id' => $id]));
        $email = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals($newEmail['subject'], $email['subject']);
    }

    /**
     * @depends testCreateEmail
     *
     * @param integer $id
     *
     * @return int
     */
    public function testUpdateEmail($id)
    {
        $this->client->request('GET', $this->getUrl('oro_api_get_email', ['id' => $id]));
        $email = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $folders   = $email['folders'];
        $folders[] = [
            'fullName' => 'INBOX \ Folder1',
            'name'     => 'Folder1',
            'type'     => 'inbox'
        ];
        $folders[] = [
            'origin'   => $folders[0]['origin'],
            'fullName' => 'INBOX \ Folder2',
            'name'     => 'Folder2',
            'type'     => 'inbox'
        ];


        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_email', ['id' => $id]),
            [
                'subject' => 'Updated subject',
                'folders' => $folders
            ]
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('oro_api_get_email', ['id' => $id]));
        $email = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals('Updated subject', $email['subject']);
        $this->assertCount(3, $email['folders']);
        $this->assertTrue($email['head']);
        $this->assertFalse($email['seen']);

        return $id;
    }

    /**
     * @depends testUpdateEmail
     *
     * @param integer $id
     */
    public function testUpdateEmailBooleanValues($id)
    {
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_email', ['id' => $id]),
            [
                'head' => 0,
                'seen' => 1
            ]
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->request('GET', $this->getUrl('oro_api_get_email', ['id' => $id]));
        $email = $this->getJsonResponseContent($this->client->getResponse(), 200);

        // check that previously set values are kept without changes
        $this->assertEquals('Updated subject', $email['subject']);
        $this->assertCount(3, $email['folders']);

        // check boolean values are set correctly
        $this->assertFalse($email['head']);
        $this->assertTrue($email['seen']);
    }
}
