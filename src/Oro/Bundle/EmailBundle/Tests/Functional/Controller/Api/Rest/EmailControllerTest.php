<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadAdminOwnerEmailData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailControllerTest extends WebTestCase
{
    private array $email = [
        'folders' => [
            ['fullName' => 'INBOX \ Test Folder', 'name' => 'Test Folder', 'type' => 'inbox']
        ],
        'subject' => 'New email',
        'messageId' => 'test@email-bundle.func-test',
        'from' => '"Address 1" <1@example.com>',
        'to' => ['"Address 2" <2@example.com>', '3@example.com'],
        'cc' => '2@example.com; "Address 3" <3@example.com>',
        'importance' => 'low',
        'body' => 'Test body',
        'bodyType' => 'text',
        'receivedAt' => '2015-06-19T12:17:51Z'
    ];

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateApiAuthHeader());
        $this->loadFixtures([LoadAdminOwnerEmailData::class]);
    }

    public function testCget()
    {
        $url = $this->getUrl('oro_api_get_emails');
        $this->client->jsonRequest('GET', $url);

        $emails = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($emails);
        $this->assertCount(10, $emails);

        $this->client->jsonRequest('GET', $url . '?messageId=' . urlencode($emails[0]['messageId']));
        $this->assertCount(1, $this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->client->jsonRequest('GET', $url . '?messageId<>' . urlencode($emails[0]['messageId']));
        $this->assertCount(9, $this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->client->jsonRequest(
            'GET',
            $url . '?messageId=' . urlencode($emails[0]['messageId'] . ',' . $emails[5]['messageId'])
        );
        $this->assertCount(2, $this->getJsonResponseContent($this->client->getResponse(), 200));

        $this->client->jsonRequest(
            'GET',
            $url . '?messageId<>' . urlencode($emails[0]['messageId'] . ',' . $emails[5]['messageId'])
        );
        $this->assertCount(8, $this->getJsonResponseContent($this->client->getResponse(), 200));
    }

    public function testGet()
    {
        $id = $this->getReference('email_1')->getId();
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_email', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
        $this->assertEquals('My Web Store Introduction', $result['subject']);
        self::assertStringContainsString('Thank you for signing up to My Web Store!', $result['body']);

        return $result['id'];
    }

    public function testCreateEmail(): int
    {
        $this->client->jsonRequest('POST', $this->getUrl('oro_api_post_email'), $this->email);
        $response = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_email', ['id' => $response['id']]));
        $email = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertTrue($email['head']);

        return $response['id'];
    }

    /**
     * @depends testCreateEmail
     */
    public function testCreateForExistingEmail(int $id)
    {
        $this->client->jsonRequest('POST', $this->getUrl('oro_api_post_email'), $this->email);
        $response = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertEquals($response['id'], $id, 'Existing email should be updated');
    }

    /**
     * @depends testCreateEmail
     */
    public function testCreateForExistingEmailButWithChangedProtectedProperty()
    {
        $newEmail = array_merge($this->email, ['subject' => 'New subject']);
        $this->client->jsonRequest('POST', $this->getUrl('oro_api_post_email'), $newEmail);
        $response = $this->getJsonResponseContent($this->client->getResponse(), 500);

        // The original exception message is returned only if functional tests are running in debug mode
        if ($this->client->getKernel()->isDebug()) {
            $this->assertEquals(
                $response['message'],
                'The Subject cannot be changed for already existing email.'
                . ' Existing value: "New email". New value: "New subject".'
            );
        }
    }

    public function testCreateEmailWithoutSubjectAndBody()
    {
        $email = $this->email;
        $email['messageId'] = 'new.test@email-bundle.func-test';
        unset($email['subject'], $email['body'], $email['bodyType']);

        $this->client->jsonRequest('POST', $this->getUrl('oro_api_post_email'), $email);
        $response = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_email', ['id' => $response['id']]));
        $email = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotNull($email['subject'], 'The Subject cannot be null. It should be empty string');
        $this->assertNotNull($email['body'], 'The Body cannot be null. It should be empty string');
    }

    /**
     * @depends testCreateEmail
     */
    public function testUpdateEmail(int $id): int
    {
        $folders = [];
        $folders[] = [
            'fullName' => 'INBOX \ Folder1',
            'name'     => 'Folder1',
            'type'     => 'inbox'
        ];
        $folders[] = [
            'fullName' => 'INBOX \ Folder2',
            'name'     => 'Folder2',
            'type'     => 'inbox'
        ];

        $this->client->jsonRequest(
            'PUT',
            $this->getUrl('oro_api_put_email', ['id' => $id]),
            [
                'seen'    => 1,
                'receivedAt' => '2015-06-19T12:17:51Z',
                'folders' => $folders
            ]
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_email', ['id' => $id]));
        $this->getJsonResponseContent($this->client->getResponse(), 200);

        return $id;
    }

    /**
     * @depends testUpdateEmail
     */
    public function testUpdateEmailProtectedProperty(int $id)
    {
        $this->client->jsonRequest(
            'PUT',
            $this->getUrl('oro_api_put_email', ['id' => $id]),
            [
                'head' => 0
            ]
        );
        $response = $this->getJsonResponseContent($this->client->getResponse(), 500);

        // The original exception message is returned only if functional tests are running in debug mode
        if ($this->client->getKernel()->isDebug()) {
            $this->assertEquals(
                $response['message'],
                'The Head cannot be changed for already existing email.'
                . ' Existing value: "true". New value: "false".'
            );
        }
    }
}
