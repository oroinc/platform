<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ResponseExtension;
use Symfony\Component\DomCrawler\Crawler;

class ControllersTest extends WebTestCase
{
    use ResponseExtension;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
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

        $eventName = 'oro.notification.event.entity_post_persist';

        $this->assertFormSubmission($eventName, $crawler);
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
                'email-notification-grid[_filter][entityName][value][]' => User::class
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

        $eventName = 'oro.notification.event.entity_post_update';

        $this->assertFormSubmission($eventName, $crawler);
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
                'email-notification-grid[_filter][entityName][value][]' => User::class
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->ajaxRequest(
            'DELETE',
            $this->getUrl('oro_api_delete_emailnotication', ['id' => $result['id']])
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }

    private function getTemplate(): EmailTemplate
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository(EmailTemplate::class)
            ->findOneBy(['entityName' => User::class]);
    }

    private function assertFormSubmission(string $eventName, Crawler $crawler): void
    {
        $form = $crawler->selectButton('Save and Close')->form();
        $formValues = $form->getPhpValues();
        $formValues['emailnotification']['entityName'] = User::class;
        $formValues['emailnotification']['eventName'] = $eventName;
        $formValues['emailnotification']['template'] = $this->getTemplate()->getId();
        $formValues['emailnotification']['recipientList']['users'] = 1;
        $formValues['emailnotification']['recipientList']['groups'][0] = 1;
        $formValues['emailnotification']['recipientList']['email'] = 'admin@example.com';

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $html = $crawler->html();
        self::assertStringContainsString('Email notification rule saved', $html);
    }
}
