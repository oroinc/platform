<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EmailTemplateControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures([LoadEmailTemplateData::class]);
    }

    public function testGetWithoutParams()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates')
        );

        $this->getJsonResponseContent($this->client->getResponse(), 404);
    }

    public function testGet()
    {
        $entityName = str_replace('\\', '_', $this->getReference('emailTemplate3')->getEntityName());
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(2, $result);
    }

    public function testGetNonSystemNoEntity()
    {
        /** @var EmailTemplate $template */
        $template = $this->getReference('emailTemplate3');
        $entityName = str_replace('\\', '_', $template->getEntityName());
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName,
                'includeNonEntity' => 0,
                'includeSystemTemplates' => 0
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                [
                    'id'          => $template->getId(),
                    'name'        => $template->getName(),
                    'is_system'   => $template->getIsSystem(),
                    'is_editable' => $template->getIsEditable(),
                    'content'     => $template->getContent(),
                    'entity_name' => $template->getEntityName(),
                    'type'        => $template->getType()
                ]
            ],
            $result
        );
    }

    public function testGetNonSystemEntity()
    {
        $entityName = str_replace('\\', '_', $this->getReference('emailTemplate3')->getEntityName());
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName,
                'includeNonEntity' => 1,
                'includeSystemTemplates' => 0
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(3, $result);
    }

    public function testGetSystemNonEntity()
    {
        $entityName = str_replace('\\', '_', $this->getReference('emailTemplate3')->getEntityName());
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName,
                'includeNonEntity' => 0,
                'includeSystemTemplates' => 1
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(2, $result);
    }

    public function testGetEntitySystem()
    {
        $reference = $this->getReference('emailTemplate3');
        $entityName = str_replace('\\', '_', $reference->getEntityName());
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName,
                'includeNonEntity' => 1,
                'includeSystemTemplates' => 1
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(13, $result);
    }

    /**
     * Check that server return rendered template with defined data structure
     */
    public function testGetCompiledEmailTemplate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $emailTemplate = $em
            ->getRepository('Oro\Bundle\EmailBundle\Entity\EmailTemplate')
            ->findOneBy(['name' => 'test_template']);

        $user = $em
            ->getRepository('Oro\Bundle\UserBundle\Entity\User')
            ->findOneBy(['username' => 'simple_user']);
        $this->assertNotNull($user);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => $user->getId()]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('body', $data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('type', $data);
    }

    /**
     * Check that server return rendered system template with defined data structure
     * Template without related entity
     */
    public function testGetCompiledSystemEmailTemplate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emailTemplate = $this->getReference('emailTemplate1');
        $user = $em
            ->getRepository('Oro\Bundle\UserBundle\Entity\User')
            ->findOneBy(['username' => 'simple_user']);
        $this->assertNotNull($user);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => '']
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('body', $data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('type', $data);
    }

    /**
     * Check that server return not found message
     */
    public function testGetCompiledEmailTemplateNoEntityFound()
    {
        $emailTemplate = $this->getReference('emailTemplate2');
        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => 0]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 404);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
    }

    /**
     * Check that server returns 422 HTTP error when failed to compile email template
     */
    public function testGetCompiledEmailCompileFailed()
    {
        $emailTemplate = $this->getReference(LoadEmailTemplateData::SYSTEM_FAIL_TO_COMPILE);
        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => 1]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 422);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('reason', $data);
    }
}
