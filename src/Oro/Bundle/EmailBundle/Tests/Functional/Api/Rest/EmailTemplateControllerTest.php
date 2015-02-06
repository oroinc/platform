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

        $emailTemplate = $em
            ->getRepository('Oro\Bundle\EmailBundle\Entity\EmailTemplate')
            ->findOneBy(['name' => 'no_entity_name']);

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
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $emailTemplate = $em
            ->getRepository('Oro\Bundle\EmailBundle\Entity\EmailTemplate')
            ->findOneBy(['name' => 'test_template']);

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
