<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class ControllersTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_email_emailtemplate_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        $this->markTestIncomplete('Skipped due to issue with dynamic form loading');
        $crawler = $this->client->request('GET', $this->getUrl('oro_email_emailtemplate_create'));
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML($crawler->html());
        $dom->getElementById('oro_email_emailtemplate');

        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_email_emailtemplate[entityName]'] = 'Oro\Bundle\UserBundle\Entity\User';
        $form['oro_email_emailtemplate[name]'] = 'User Template';
        $form['oro_email_emailtemplate[translations][defaultLocale][en][content]'] = 'Content template';
        $form['oro_email_emailtemplate[translations][defaultLocale][en][subject]'] = 'Subject';
        $form['oro_email_emailtemplate[type]'] = 'html';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Template saved", $crawler->html());
    }
}
