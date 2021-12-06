<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowEmailTemplates;
use Oro\Component\Testing\ResponseExtension;
use Symfony\Component\DomCrawler\Crawler;

class EmailNotificationControllerTest extends WebTestCase
{
    use ResponseExtension;

    private const ENTITY_NAME = WorkflowAwareEntity::class;
    private const EVENT_NAME = 'oro.workflow.event.notification.workflow_transition';
    private const TRANSITION_NAME = 'starting_point_transition';

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadWorkflowDefinitions::class, LoadWorkflowEmailTemplates::class]);
    }

    public function testCreate(): string
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_notification_emailnotification_create'));
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeHtml();

        $eventName = self::EVENT_NAME;

        $this->assertFormSubmission($eventName, $crawler);
        $response = $this->client->requestGrid(
            'email-notification-grid',
            [
                'email-notification-grid[_filter][workflow_transition_name][value]' => self::TRANSITION_NAME
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        return $result['id'];
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(string $id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_notification_emailnotification_update', ['id' => $id])
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeHtml();

        $eventName = self::EVENT_NAME;

        $this->assertFormSubmission($eventName, $crawler);
    }

    /**
     * @depends testCreate
     */
    public function testDelete(string $id)
    {
        $this->ajaxRequest(
            'DELETE',
            $this->getUrl('oro_api_delete_emailnotication', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }

    private function getTemplate(): EmailTemplate
    {
        return $this->getReference(LoadWorkflowEmailTemplates::WFA_EMAIL_TEMPLATE_NAME);
    }

    private function assertFormSubmission(string $eventName, Crawler $crawler): void
    {
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['emailnotification']['entityName'] = self::ENTITY_NAME;
        $formValues['emailnotification']['eventName'] = $eventName;
        $formValues['emailnotification']['template'] = $this->getTemplate()->getId();
        $formValues['emailnotification']['recipientList']['users'] = 1;
        $formValues['emailnotification']['recipientList']['groups'][0] = 1;
        $formValues['emailnotification']['recipientList']['email'] = 'admin@example.com';
        $formValues['emailnotification']['workflow_definition'] = 'test_multistep_flow';
        $formValues['emailnotification']['workflow_transition_name'] = self::TRANSITION_NAME;

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();
        self::assertStringContainsString('Email notification rule saved', $html);
    }
}
