<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailControllerTest extends WebTestCase
{
    const INCORRECT_ID = 1111;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->loadFixtures(['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailData']);
    }

    /**
     * @return array
     */
    public function testCget()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_emails'
            )
        );

        $this->getJsonResponseContent($this->client->getResponse(), 200);
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
        $this->assertContains('Thank you for signing up to My Web Store!', $result['emailBody']['content']);

        return $result['id'];
    }

    public function testGetAssociation()
    {
        $this->getAssosiaction(self::INCORRECT_ID);
        $this->getJsonResponseContent($this->client->getResponse(), 404);

        $id = $this->getReference('email_1')->getId();
        $this->getAssosiaction($id);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);


        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
    }

    public function testGetAssociatioData()
    {
        $id = $this->getReference('email_1')->getId();
        $this->getAssosiactionData($id);

//        $this->assertNotEmpty($result);
//        $this->assertCount(2, $result);
    }

    public function testDeleteAssociation()
    {
        $userId = $this->getReference('simple_user2')->getId();
        $this->deleteAssociation(self::INCORRECT_ID, 'Oro\Bundle\UserBundle\Entity\User', $userId);
        $this->getJsonResponseContent($this->client->getResponse(), 404);

        $id = $this->getReference('email_1')->getId();
        $userId = $this->getReference('simple_user2')->getId();
        $this->deleteAssociation($id, 'Oro\Bundle\UserBundle\Entity\User', $userId);
        return  $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
    }

    public function testPostAssociation()
    {
        $userId = $this->getReference('simple_user2')->getId();
        $this->postAssociation(self::INCORRECT_ID, 'Oro\Bundle\UserBundle\Entity\User', $userId);
        $this->getJsonResponseContent($this->client->getResponse(), 404);

        $id = $this->getReference('email_1')->getId();
        $userId = $this->getReference('simple_user2')->getId();

        $this->getAssosiaction($id);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);

        $this->postAssociation($id, 'Oro\Bundle\UserBundle\Entity\User', $userId);
        $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->getAssosiaction($id);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
    }


    protected function getAssosiactionData($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_email_associations_data', ['entityId' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        return $result;
    }


    protected function getAssosiaction($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_email_association', ['entityId' => $id])
        );
    }

    protected function deleteAssociation($enityId, $targetClassName, $targetId)
    {
        $param = [
            'entityId' => $enityId,
            'targetClassName'=>$targetClassName,
            'targetId'=>$targetId
        ];

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_email_association', $param)
        );
    }

    protected function postAssociation($enityId, $targetClassName, $targetId)
    {
        $param = [
            'entityId' => $enityId,
            'targetClassName'=>$targetClassName,
            'targetId'=>$targetId
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_email_associations', $param)
        );
    }
}
