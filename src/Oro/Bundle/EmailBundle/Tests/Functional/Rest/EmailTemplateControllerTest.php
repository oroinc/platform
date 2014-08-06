<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Rest;

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
            ->findOneBy([]);

        $calendarEvent = $em
            ->getRepository('Oro\Bundle\CalendarBundle\Entity\CalendarEvent')
            ->findOneBy([]);
        $this->assertNotNull($calendarEvent);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => $calendarEvent->getId()]
            )
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('body', $data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('type', $data);
    }

    /**
     * Check that server return rendered template with defined data structure
     */
    public function testGetCompiledEmailTemplateNoEntityFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $emailTemplate = $em
            ->getRepository('Oro\Bundle\EmailBundle\Entity\EmailTemplate')
            ->findOneBy([]);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => 0]
            )
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 404);

        $data = json_decode($result->getContent(), true);

        $this->assertInternalType('array', $data);
        $this->assertArrayHasKey('message', $data);
    }
}
