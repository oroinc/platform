<?php

namespace Oro\Bundle\UserBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ControllersRoleTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_user_role_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', $this->getUrl('oro_user_role_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_user_role_form[label]'] = 'testRole';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Role saved", $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testClone()
    {
        $response = $this->client->requestGrid(
            'roles-grid',
            array('roles-grid[_filter][label][value]' => 'testRole')
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_role_clone', array('id' => $result['id']))
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();
        $form['oro_user_role_form[label]'] = 'clonedRole';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Role saved", $crawler->html());
        $this->assertContains("clonedRole", $crawler->html());
        $this->assertContains("testRole", $crawler->html());
    }

    /**
     * @depends testCreate
     */
    public function testUpdate()
    {
        $response = $this->client->requestGrid(
            'roles-grid',
            ['roles-grid[_filter][label][value]' => 'testRole']
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_role_update', ['id' => $result['id']])
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $form['oro_user_role_form[label]']       = 'testRoleUpdated';
        $form['oro_user_role_form[appendUsers]'] = 1;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Role saved", $crawler->html());
    }

    /**
     * Test role fields UI
     *
     * @return Crawler
     */
    public function testRoleFields()
    {
        $response = $this->client->requestGrid(
            'roles-grid',
            ['roles-grid[_filter][label][value]' => 'testRoleUpdated']
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        /** @var Crawler $crawler */
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_role_update', ['id' => $result['id']])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $roleEntityViewLinks = $crawler->filter('div[id^="oro_user_role_form_entity_"] a');
        $this->assertGreaterThan(
            50,
            count($roleEntityViewLinks),
            'Failed asserting entity view links found on the page'
        );

        $entityName = 'Account';
        /** @var \DOMElement $entityLink */
        foreach ($roleEntityViewLinks as $entityLink) {
            if (trim($entityLink->textContent) == $entityName) {
                $entityLink = $entityLink->getAttribute('href');

                break;
            }
        }
        $this->assertTrue(is_string($entityLink), 'Fail to found entity link.');

        return $entityLink;
    }

    /**
     * @depends testRoleFields
     *
     * @param string $entityLink
     *
     * @return Crawler
     */
    public function testRoleEntityFields($entityLink)
    {
        $crawler = $this->client->request('GET', $entityLink);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $fields = $crawler->filter('div[id^="oro_user_role_form_field_"] strong');

        $this->assertEquals(
            16,
            count($fields),
            'Failed asserting that all fields are available on UI'
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save')->form();

        $fieldNumber = null;
        /** @var \DOMElement $fieldInput */
        foreach ($fields as $i => $fieldInput) {
            $fieldName = trim($fieldInput->textContent);
            if ('name' == $fieldName) {
                $fieldNumber = $i;

                break;
            }
        }
        $this->assertNotNull($fieldNumber, 'Failed: field number not found.');

        // set field view permission to NONE
        $fieldViewInput = sprintf(
            'oro_user_role_form[field][%s][permissions][%s][accessLevel]',
            $fieldNumber,
            'VIEW'
        );
        $form[$fieldViewInput] = AccessLevel::NONE_LEVEL;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Role saved", $crawler->html());

        return $entityLink;
    }

    /**
     * @depends testRoleEntityFields
     *
     * @param string $entityLink
     */
    public function testAccessLevelHasBeenChanged($entityLink)
    {
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', $entityLink);

        $fields = $crawler->filter('div[id^="oro_user_role_form_field_"] strong');
        $fieldNumber = null;
        /** @var \DOMElement $fieldInput */
        foreach ($fields as $i => $fieldInput) {
            $fieldName = trim($fieldInput->textContent);
            if ('name' == $fieldName) {
                $fieldNumber = $i;

                break;
            }
        }
        $this->assertNotNull($fieldNumber, 'Failed: field number not found.');

        // set field view permission to NONE
        $fieldViewInput = sprintf('oro_user_role_form[field][%s][permissions][%s][accessLevel]', $fieldNumber, 'VIEW');
        $fieldInput     = $crawler
            ->filter(sprintf('input[name="%s"]', $fieldViewInput))
            ->getNode(0);

        $this->assertEquals(AccessLevel::NONE_LEVEL, $fieldInput->getAttribute('value'));
    }

    /**
     * @depends testUpdate
     */
    public function testGridData()
    {
        $response = $this->client->requestGrid(
            'roles-grid',
            ['roles-grid[_filter][label][value]' => 'testRoleUpdated']
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $response = $this->client->requestGrid(
            'role-users-grid',
            [
                'role-users-grid[_filter][has_role][value]' => 1,
                'role-users-grid[role_id]'                  => $result['id']
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $this->assertEquals(1, $result['id']);
    }
}
