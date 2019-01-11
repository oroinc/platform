<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\NotificationBundle\Entity\Event;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\ResponseExtension;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class ControllersTest extends WebTestCase
{
    use ResponseExtension;

    const ENTITY_NAME = 'Oro\Bundle\UserBundle\Entity\User';

    /** @var  Registry */
    protected $doctrine;

    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->client->useHashNavigation(true);
        $this->doctrine = $this->getContainer()->get('doctrine');
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_notification_emailnotification_index'));

        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeHtml();
    }

    /**
     * @depends testIndex
     */
    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_notification_emailnotification_create'));
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeHtml();

        $event = $this->getEvent('oro.notification.event.entity_post_persist');

        $this->assertFormSubmission($event, $crawler);
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'email-notification-grid',
            [
                'email-notification-grid[_pager][_page]' => 1,
                'email-notification-grid[_pager][_per_page]' => 1,
                'email-notification-grid[_filter][entityName][value][]' => self::ENTITY_NAME
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_notification_emailnotification_update', ['id' => $result['id']])
        );
        $this->assertLastResponseStatus(200);
        $this->assertLastResponseContentTypeHtml();

        $event = $this->getEvent('oro.notification.event.entity_post_update');

        $this->assertFormSubmission($event, $crawler);
    }

    /**
     * @depends testCreate
     */
    public function testDelete()
    {
        $response = $this->client->requestGrid(
            'email-notification-grid',
            [
                'email-notification-grid[_pager][_page]' => 1,
                'email-notification-grid[_pager][_per_page]' => 1,
                'email-notification-grid[_filter][entityName][value][]' => self::ENTITY_NAME
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_emailnotication', ['id' => $result['id']])
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
        return $this->doctrine
            ->getRepository(EmailTemplate::class)
            ->findOneBy(['entityName' => self::ENTITY_NAME]);
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

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();
        $this->assertContains("Email notification rule saved", $html);
    }
}
