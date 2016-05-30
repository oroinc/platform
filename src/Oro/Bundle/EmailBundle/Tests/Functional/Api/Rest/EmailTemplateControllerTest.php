<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class EmailTemplateControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

        $this->loadFixtures(
            [
                'Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateData'
            ]
        );
    }

    public function testGetWithoutParams()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates')
        );

        $this->getJsonResponseContent($this->client->getResponse(), 404);
    }

    public function testGet()
    {
        $entityName = str_replace('\\', '_', $this->getReference('emailTemplate3')->getEntityName());
        $this->client->request(
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
        $entityName = str_replace('\\', '_', $this->getReference('emailTemplate3')->getEntityName());
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName,
                'includeNonEntity' => 0,
                'includeSystemTemplates' => 0
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);
    }

    public function testGetNonSystemEntity()
    {
        $entityName = str_replace('\\', '_', $this->getReference('emailTemplate3')->getEntityName());
        $this->client->request(
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
        $this->client->request(
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
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName,
                'includeNonEntity' => 1,
                'includeSystemTemplates' => 1
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(5, $result);
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

        $calendarEvent = $em
            ->getRepository('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->findOneBy(['title' => 'test_title']);
        $this->assertNotNull($calendarEvent);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => $calendarEvent->getId()]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertInternalType('array', $data);
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
        $calendarEvent = $em
            ->getRepository('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->findOneBy(['title' => 'test_title']);
        $this->assertNotNull($calendarEvent);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => '']
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertInternalType('array', $data);
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
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => 0]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 404);

        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('message', $data);
    }
}
