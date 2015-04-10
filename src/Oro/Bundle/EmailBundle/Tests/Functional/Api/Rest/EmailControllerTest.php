<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EmailControllerTest extends WebTestCase
{
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
        $id = $this->getReference('email_1')->getId();
        $result = $this->getAssosiaction($id);

        $this->assertNotEmpty($result);
        $this->assertCount(2, $result);
    }

    public function testDeleteAssociation()
    {
        $id = $this->getReference('email_1')->getId();
        $userId = $this->getReference('simple_user2')->getId();
        $result = $this->deleteAssociation($id, 'Oro\Bundle\UserBundle\Entity\User', $userId);

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
    }

    public function testDeleteAssociations()
    {
        $id = $this->getReference('email_1')->getId();
        $result = $this->deleteAssociations($id);

        $this->assertEmpty($result);
    }

    public function testPostAssociation()
    {
        $id = $this->getReference('email_1')->getId();
        $userId = $this->getReference('simple_user2')->getId();

        $result = $this->getAssosiaction($id);
        $this->assertCount(0, $result);

        $this->postAssociation($id, 'Oro\Bundle\UserBundle\Entity\User', $userId);

        $result = $this->getAssosiaction($id);
        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
    }


    protected function getAssosiaction($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_email_association', ['entityId' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        return $result;
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

        return  $this->getJsonResponseContent($this->client->getResponse(), 200);
    }

    protected function deleteAssociations($id)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_email_associations', ['entityId' => $id])
        );

        return $this->getJsonResponseContent($this->client->getResponse(), 200);
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

        return $this->getJsonResponseContent($this->client->getResponse(), 200);
    }
}
