<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowDefinitions;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadWorkflowEmailTemplates;
use Oro\Component\Testing\ResponseExtension;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class EmailNotificationControllerTest extends WebTestCase
{
    use ResponseExtension;

    const ENTITY_NAME = WorkflowAwareEntity::class;
    const EVENT_NAME = 'oro.workflow.event.notification.workflow_transition';
    const TRANSITION_NAME = 'starting_point_transition';

    /** @var  Registry */
    protected $doctrine;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->client->useHashNavigation(true);
        $this->doctrine = $this->getContainer()->get('doctrine');
        $this->loadFixtures([LoadWorkflowDefinitions::class, LoadWorkflowEmailTemplates::class]);
    }

    /**
     * @return string
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_notification_emailnotification_create'));
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeHtml();

        $event = $this->getEvent(self::EVENT_NAME);

        $this->assertFormSubmission($event, $crawler);
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
     *
     * @param string $id
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_notification_emailnotification_update', ['id' => $id])
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeHtml();

        $event = $this->getEvent(self::EVENT_NAME);

        $this->assertFormSubmission($event, $crawler);
    }

    /**
     * @depends testCreate
     *
     * @param $id
     */
    public function testDelete($id)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_emailnotication', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }

    /**
     * @param $eventName
     *
     * @return null|object|Event
     */
    protected function getEvent($eventName)
    {
        return $this->doctrine->getRepository(Event::class)
            ->findOneBy(['name' => $eventName]);
    }

    /**
     * @return null|object|EmailTemplate
     */
    protected function getTemplate()
    {
        return $this->getReference(LoadWorkflowEmailTemplates::WFA_EMAIL_TEMPLATE_NAME);
    }

    /**
     * @param Event $event
     * @param Crawler $crawler
     */
    protected function assertFormSubmission(Event $event, Crawler $crawler)
    {
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['emailnotification']['entityName'] = self::ENTITY_NAME;
        $formValues['emailnotification']['event'] = $event->getId();
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
        $this->assertContains("Email notification rule saved", $html);
    }
}
