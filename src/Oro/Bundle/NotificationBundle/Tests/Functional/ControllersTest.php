<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional;

use Oro\Component\Testing\ResponseExtension;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ControllersTest extends WebTestCase
{
    use ResponseExtension;

    const ENTITY_NAME = 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent';

    protected $eventUpdate;
    protected $eventCreate;
    protected $templateUpdate;

    protected function setUp()
    {
        $this->initClient(
            array(),
            array_merge($this->generateBasicAuthHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
        $this->client->useHashNavigation(true);
    }

    protected function prepareData()
    {
        $notificationManager = $this->getContainer()->get('doctrine');
        $this->eventUpdate  = $notificationManager
            ->getRepository('OroNotificationBundle:Event')
            ->findOneBy(array('name' => 'oro.notification.event.entity_post_update'));

        $this->eventCreate  = $notificationManager
            ->getRepository('OroNotificationBundle:Event')
            ->findOneBy(array('name' => 'oro.notification.event.entity_post_persist'));

        $this->templateUpdate  = $notificationManager
            ->getRepository('OroEmailBundle:EmailTemplate')
            ->findOneBy(array('entityName' => self::ENTITY_NAME));
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

        // prepare data for next tests
        $this->prepareData();

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['emailnotification[entityName]'] = self::ENTITY_NAME;
        $form['emailnotification[event]'] = $this->eventUpdate->getId();
        $doc = $this->createSelectTemplate();
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['emailnotification[template]'] = $this->templateUpdate->getId();
        $form['emailnotification[recipientList][users]'] = '1';
        $form['emailnotification[recipientList][groups][0]'] = '1';
        $form['emailnotification[recipientList][email]'] = 'admin@example.com';
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Email notification rule saved", $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'email-notification-grid',
            array(
                'email-notification-grid[_pager][_page]' => 1,
                'email-notification-grid[_pager][_per_page]' => 1
            )
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_notification_emailnotification_update', array('id' => $result['id']))
        );

        // prepare data for next tests
        $this->prepareData();

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['emailnotification[entityName]'] = self::ENTITY_NAME;
        $form['emailnotification[event]'] = $this->eventCreate->getId();
        $doc = $this->createSelectTemplate();
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form['emailnotification[template]'] = $this->templateUpdate->getId();
        $form['emailnotification[recipientList][users]'] = '1';
        $form['emailnotification[recipientList][groups][0]'] = '1';
        $form['emailnotification[recipientList][email]'] = 'admin@example.com';
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Email notification rule saved", $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testDelete()
    {
        $response = $this->client->requestGrid(
            'email-notification-grid',
            array(
                'email-notification-grid[_pager][_page]' => 1,
                'email-notification-grid[_pager][_per_page]' => 1,
            )
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_emailnotication', array('id' => $result['id']))
        );

        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }

    protected function createSelectTemplate()
    {
        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select required="required" name="emailnotification[template]" id="emailnotification_template" ' .
            'tabindex="-1" class="select2-offscreen"> ' .
            '<option value="" selected="selected"></option> ' .
            '<option value="' . $this->templateUpdate->getId() . '">' .
            'EmailBundle:' . $this->templateUpdate->getName() .
            '</option> </select>'
        );
        return $doc;
    }
}
