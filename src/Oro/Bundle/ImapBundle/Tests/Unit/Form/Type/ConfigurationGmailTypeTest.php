<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationGmailType;
use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ConfigurationGmailTypeTest extends FormIntegrationTestCase
{
    /** @var TokenAccessorInterface|MockObject */
    protected $tokenAccessor;

    /** @var Translator|MockObject */
    protected $translator;

    /** @var MockObject */
    protected $configProvider;

    /** @var ConfigManager|MockObject */
    protected $userConfigManager;

    protected function setUp(): void
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['getOrganization'])
            ->getMock();
        $organization = $this->createMock(Organization::class);
        $user->method('getOrganization')->willReturn($organization);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->tokenAccessor->method('getUser')->willReturn($user);
        $this->tokenAccessor->method('getOrganization')->willReturn($organization);

        $this->translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $this->userConfigManager = $this->getMockBuilder(ConfigManager::class)->disableOriginalConstructor()->getMock();

        $this->configProvider = $this
            ->getMockBuilder(ConfigProvider::class)
            ->onlyMethods(['hasConfig', 'getConfig'])
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    protected function getExtensions()
    {
        $type = new ConfigurationGmailType($this->translator, $this->userConfigManager, $this->tokenAccessor);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        CheckButtonType::class => new CheckButtonType(),
                        EmailFolderTreeType::class => new EmailFolderTreeType(),
                        ConfigurationGmailType::class => $type
                    ],
                    [
                        FormType::class => [new TooltipFormExtension($this->configProvider, $this->translator)],
                    ]
                ),
            ]
        );
    }

    /**
     * Test default values for form ConfigurationGmailType
     */
    public function testDefaultData()
    {
        $form = $this->factory->create(ConfigurationGmailType::class);

        static::assertEquals(GmailImap::DEFAULT_GMAIL_HOST, $form->get('imapHost')->getData());
        static::assertEquals(GmailImap::DEFAULT_GMAIL_PORT, $form->get('imapPort')->getData());
        static::assertEquals(GmailImap::DEFAULT_GMAIL_SSL, $form->get('imapEncryption')->getData());
        static::assertEquals(GmailImap::DEFAULT_GMAIL_SMTP_HOST, $form->get('smtpHost')->getData());
        static::assertEquals(GmailImap::DEFAULT_GMAIL_SMTP_PORT, $form->get('smtpPort')->getData());
        static::assertEquals(GmailImap::DEFAULT_GMAIL_SMTP_SSL, $form->get('smtpEncryption')->getData());
    }

    public function testShouldBindValidData()
    {
        $accessTokenExpiresAt = new \DateTime();

        $form = $this->factory->create(ConfigurationGmailType::class);
        $form->submit([
            'user' => 'test',
            'imapHost' => 'imap.gmail.com',
            'imapPort' => '993',
            'imapEncryption' => 'ssl',
            'smtpHost' => 'smtp.gmail.com',
            'smtpPort' => '993',
            'smtpEncryption' => 'ssl',
            'accessTokenExpiresAt' => $accessTokenExpiresAt,
            'accessToken' => '1',
            'refreshToken' => '111'
        ]);

        static::assertEquals('test', $form->get('user')->getData());
        static::assertEquals('imap.gmail.com', $form->get('imapHost')->getData());
        static::assertEquals('993', $form->get('imapPort')->getData());
        static::assertEquals('ssl', $form->get('imapEncryption')->getData());
        static::assertEquals('smtp.gmail.com', $form->get('smtpHost')->getData());
        static::assertEquals('993', $form->get('smtpPort')->getData());
        static::assertEquals('ssl', $form->get('smtpEncryption')->getData());
        static::assertEquals($accessTokenExpiresAt, $form->get('accessTokenExpiresAt')->getData());
        static::assertEquals('1', $form->get('accessToken')->getData());
        static::assertEquals('111', $form->get('refreshToken')->getData());

        $entity = $form->getData();

        static::assertEquals('test', $entity->getUser());
        static::assertEquals('imap.gmail.com', $entity->getImapHost());
        static::assertEquals('993', $entity->getImapPort());
        static::assertEquals('ssl', $entity->getImapEncryption());
        static::assertEquals('smtp.gmail.com', $entity->getSmtpHost());
        static::assertEquals('993', $entity->getSmtpPort());
        static::assertEquals('ssl', $entity->getSmtpEncryption());
        static::assertEquals($accessTokenExpiresAt, $entity->getAccessTokenExpiresAt());
        static::assertEquals('1', $entity->getAccessToken());
        static::assertEquals('111', $entity->getRefreshToken());
    }
}
