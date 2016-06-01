<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ControllersTest extends WebTestCase
{
    /**
     * @var Registry
     */
    protected $registry;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->registry = $this->getContainer()->get('doctrine');
        $this->loadFixtures(['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadUserData']);
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

    /**
     * @dataProvider autoCompleteHandlerProvider
     * @param boolean $active
     * @param string $handlerName
     */
    public function testAutoCompleteHandler($active, $handlerName, $query)
    {
        $user = $this->registry->getRepository('OroUserBundle:User')->findOneBy(['username' => 'simple_user2']);
        $user->setEnabled($active);
        $this->registry->getManager()->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_email_mailbox_users_search', ['organizationId' => $user->getOrganization()->getId()]),
            array(
                'page' => 1,
                'per_page' => 10,
                'name' => $handlerName,
                'query' => $query,
            )
        );

        $result = $this->client->getResponse();
        $arr = $this->getJsonResponseContent($result, 200);
        $this->assertCount((int)$active, $arr['results']);
    }

    /**
     * @return array
     */
    public function autoCompleteHandlerProvider()
    {
        return array(
                'Mailbox user autocomplete handler active' =>
                array(
                    'active' => true,
                    'handler' => 'users',
                    'query' => 'Elley Towards'
                ),
                'Mailbox user autocomplete handler inactive' =>
                array(
                    'active' => false,
                    'handler' => 'users',
                    'query' => 'Elley Towards'
                )
        );
    }
}
