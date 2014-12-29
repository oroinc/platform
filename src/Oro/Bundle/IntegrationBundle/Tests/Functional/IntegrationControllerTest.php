<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class IntegrationControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            array(),
            array_merge($this->generateBasicAuthHeader(), array('HTTP_X-CSRF-Header' => 1))
        );
    }

    /**
     * @return \Oro\Bundle\OrganizationBundle\Entity\Organization|null
     */
    public function getOrganization()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroOrganizationBundle:Organization')
            ->findOneByName(LoadOrganizationAndBusinessUnitData::MAIN_ORGANIZATION);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_integration_index'));
        $result  = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Integrations - System', $crawler->html());
    }

    public function testCreate()
    {
        $this->markTestIncomplete('Skipped due to issue with dynamic form loading');

        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var User $user */
        $user    = $this->getContainer()->get('security.context')->getToken()->getUser();
        $newUser = clone $user;
        $newUser->setUsername('new username');
        $newUser->setEmail(mt_rand() . $user->getEmail());
        $entityManager->persist($newUser);
        $entityManager->flush($newUser);

        $organization = $this->getOrganization();
        $crawler      = $this->client->request('GET', $this->getUrl('oro_integration_create'));
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $this->assertEquals(
            $user->getId(),
            $form['oro_integration_channel_form[defaultUserOwner]']->getValue(),
            'Should contain predefined default owner - current user'
        );

        $this->assertEquals(
            $organization->getId(),
            $form['oro_integration_channel_form[organization]']->getValue(),
            'Should contain predefined organization'
        );

        $name                                                   = 'name' . $this->generateRandomString();
        $form['oro_integration_channel_form[name]']             = 'Simple channel';
        $form['oro_integration_channel_form[organization]']     = $organization->getId();
        $form['oro_integration_channel_form[type]']             = 'simple';
        $form['oro_integration_channel_form[defaultUserOwner]'] = $newUser->getId();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Integration saved", $crawler->html());

        return compact('name', 'newUser', 'organization');
    }

    /**
     * @param array $data
     *
     * @depends testCreate
     *
     * @return array
     */
    public function testUpdate($data)
    {
        $response = $this->client->requestGrid(
            'oro-integration-grid',
            array('channels[_filter][name][value]' => $data['name'])
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $integration = $result;
        $crawler     = $this->client->request(
            'GET',
            $this->getUrl('oro_integration_update', array('id' => $result['id']))
        );

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $this->assertEquals(
            $data['newUser']->getId(),
            $form['oro_integration_channel_form[defaultUserOwner]']->getValue(),
            'Should save default user owner'
        );

        $this->assertEquals(
            $data['organization']->getId(),
            $form['oro_integration_channel_form[organization]']->getValue(),
            'Should save organization'
        );

        $name                                       = 'name' . $this->generateRandomString();
        $form['oro_integration_channel_form[name]'] = $name;

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200, 'text/html; charset=UTF-8');
        $this->assertContains("Integration saved", $crawler->html());

        $integration['name'] = $name;
        return $integration;
    }

    /**
     * @param $integration
     *
     * @depends testUpdate
     *
     * @return string
     */
    public function testSchedule($integration)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_integration_schedule', array('id' => $integration['id']))
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result['job_id']);
    }

    /**
     * @param $integration
     *
     * @depends testUpdate
     */
    public function testDelete($integration)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_integration', array('id' => $integration['id']))
        );

        $response = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($response, 204);

        $response = $this->client->requestGrid(
            'oro-integration-grid',
            array('channels[_filter][name][value]' => $integration['name'])
        );

        $result = $this->getJsonResponseContent($response, 200);

        $this->assertEmpty($result['data']);
        $this->assertEmpty($result['options']['totalRecords']);
    }
}
