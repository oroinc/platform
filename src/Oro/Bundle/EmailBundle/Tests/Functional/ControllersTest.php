<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional;

use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class ControllersTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadUserData::class]);
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
        $form['oro_email_emailtemplate[entityName]'] = User::class;
        $form['oro_email_emailtemplate[name]'] = 'User Template';
        $form['oro_email_emailtemplate[translations][defaultLocale][en][content]'] = 'Content template';
        $form['oro_email_emailtemplate[translations][defaultLocale][en][subject]'] = 'Subject';
        $form['oro_email_emailtemplate[type]'] = 'html';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Template saved', $crawler->html());
    }

    /**
     * @dataProvider autoCompleteHandlerProvider
     */
    public function testAutoCompleteHandler(bool $active, string $handlerName, string $query)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $user = $doctrine->getRepository(User::class)->findOneBy(['username' => 'simple_user2']);
        $user->setEnabled($active);
        $doctrine->getManager()->flush();

        $this->client->request(
            'GET',
            $this->getUrl('oro_email_mailbox_users_search', ['organizationId' => $user->getOrganization()->getId()]),
            [
                'page' => 1,
                'per_page' => 10,
                'name' => $handlerName,
                'query' => $query,
            ]
        );

        $result = $this->client->getResponse();
        $arr = $this->getJsonResponseContent($result, 200);
        $this->assertCount((int)$active, $arr['results']);
    }

    public function autoCompleteHandlerProvider(): array
    {
        return [
                'Mailbox user autocomplete handler active' =>
                [
                    'active' => true,
                    'handler' => 'users',
                    'query' => 'Elley Towards'
                ],
                'Mailbox user autocomplete handler inactive' =>
                [
                    'active' => false,
                    'handler' => 'users',
                    'query' => 'Elley Towards'
                ]
        ];
    }
}
