<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Form\Handler;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\DomCrawler\Crawler;

class UserEmailConfigHandlerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadUserData::class]);
    }

    public function testSaveWithoutSelectedFolders()
    {
        $configuration = [
            'imapConfiguration' => [
                'accountType' => 'other',
                'useImap' => true,
                'imapHost' => 'host',
                'imapPort' => 1,
                'imapEncryption' => 'ssl',
                'user' => 'test@demo.com',
                'password' => ''
            ]
        ];

        $crawler = $this->saveConfiguration($configuration);
        self::assertStringContainsString(
            'At least one folder of mailbox is required to be selected.',
            $crawler->html()
        );

        $emailOrigins = $this->getContainer()->get('doctrine')->getRepository(EmailOrigin::class)
            ->findAll();
        $this->assertEmpty($emailOrigins);
    }

    public function testSaveWithSelectedFolders()
    {
        $configuration = [
            'imapConfiguration' => [
                'accountType' => 'other',
                'useImap' => true,
                'imapHost' => 'host',
                'imapPort' => 1,
                'imapEncryption' => 'ssl',
                'useSmtp' => true,
                'smtpHost' => 'host',
                'smtpPort' => 1,
                'smtpEncryption' => 'ssl',
                'user' => 'test@demo.com',
                'password' => '',
                'folders' => [
                    [
                        'syncEnabled' => 'on',
                        'fullName' => '[Gmail]/Trash',
                        'name' => 'Trash',
                        'type' => 'trash',
                        'subFolders' => []
                    ]
                ]
            ]
        ];

        $crawler = $this->saveConfiguration($configuration);
        self::assertStringNotContainsString(
            'At least one folder of mailbox is required to be selected.',
            $crawler->html()
        );
        self::assertStringContainsString('Could not establish the IMAP connection', $crawler->html());
        self::assertStringContainsString('Could not establish the SMTP connection', $crawler->html());

        $emailOrigins = $this->getContainer()->get('doctrine')->getRepository(EmailOrigin::class)
            ->findAll();
        $this->assertEmpty($emailOrigins);
    }

    private function saveConfiguration(array $configurationData): Crawler
    {
        $parameters = [
            'activeGroup' => 'platform',
            'activeSubGroup' => 'user_email_configuration'
        ];

        $crawler = $this->client->request('GET', $this->getUrl('oro_user_profile_configuration', $parameters));

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save settings')->form();
        $formValues = $form->getPhpValues();
        $formValues['user_email_configuration']['oro_email___user_mailbox']['value'] = $configurationData;

        // Submit form
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        return $crawler;
    }
}
