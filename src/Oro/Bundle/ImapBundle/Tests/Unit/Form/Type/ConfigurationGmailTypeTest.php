<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Form\Type\EmailFolderTreeType;
use Oro\Bundle\FormBundle\Tests\Unit\Stub\TooltipFormExtensionStub;
use Oro\Bundle\ImapBundle\Form\Type\CheckButtonType;
use Oro\Bundle\ImapBundle\Form\Type\ConfigurationGmailType;
use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfigurationGmailTypeTest extends FormIntegrationTestCase
{
    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userConfigManager;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var OAuthManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $oauthManagerRegistry;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->userConfigManager = $this->createMock(ConfigManager::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);

        $user = $this->createMock(User::class);
        $organization = $this->createMock(Organization::class);
        $user->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);
        $this->tokenAccessor->expects(self::any())
            ->method('getUser')
            ->willReturn($user);
        $this->tokenAccessor->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);

        $request = $this->createMock(Request::class);
        $request->expects(self::any())
            ->method('isXmlHttpRequest')
            ->willReturn(false);
        $request->expects(self::any())
            ->method('get')
            ->willReturn('sample');
        $this->requestStack->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension(
                    [
                        new CheckButtonType(),
                        new EmailFolderTreeType(),
                        new ConfigurationGmailType(
                            $this->createMock(TranslatorInterface::class),
                            $this->userConfigManager,
                            $this->tokenAccessor,
                            $this->requestStack,
                            $this->oauthManagerRegistry
                        )
                    ],
                    [
                        FormType::class => [new TooltipFormExtensionStub($this)]
                    ]
                ),
            ]
        );
    }

    public function testDefaultData()
    {
        $form = $this->factory->create(ConfigurationGmailType::class);

        self::assertEquals(GmailImap::DEFAULT_GMAIL_HOST, $form->get('imapHost')->getData());
        self::assertEquals(GmailImap::DEFAULT_GMAIL_PORT, $form->get('imapPort')->getData());
        self::assertEquals(GmailImap::DEFAULT_GMAIL_SSL, $form->get('imapEncryption')->getData());
        self::assertEquals(GmailImap::DEFAULT_GMAIL_SMTP_HOST, $form->get('smtpHost')->getData());
        self::assertEquals(GmailImap::DEFAULT_GMAIL_SMTP_PORT, $form->get('smtpPort')->getData());
        self::assertEquals(GmailImap::DEFAULT_GMAIL_SMTP_SSL, $form->get('smtpEncryption')->getData());
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

        self::assertEquals('test', $form->get('user')->getData());
        self::assertEquals('imap.gmail.com', $form->get('imapHost')->getData());
        self::assertEquals('993', $form->get('imapPort')->getData());
        self::assertEquals('ssl', $form->get('imapEncryption')->getData());
        self::assertEquals('smtp.gmail.com', $form->get('smtpHost')->getData());
        self::assertEquals('993', $form->get('smtpPort')->getData());
        self::assertEquals('ssl', $form->get('smtpEncryption')->getData());
        self::assertEquals($accessTokenExpiresAt, $form->get('accessTokenExpiresAt')->getData());
        self::assertEquals('1', $form->get('accessToken')->getData());
        self::assertEquals('111', $form->get('refreshToken')->getData());

        $entity = $form->getData();

        self::assertEquals('test', $entity->getUser());
        self::assertEquals('imap.gmail.com', $entity->getImapHost());
        self::assertEquals('993', $entity->getImapPort());
        self::assertEquals('ssl', $entity->getImapEncryption());
        self::assertEquals('smtp.gmail.com', $entity->getSmtpHost());
        self::assertEquals('993', $entity->getSmtpPort());
        self::assertEquals('ssl', $entity->getSmtpEncryption());
        self::assertEquals($accessTokenExpiresAt, $entity->getAccessTokenExpiresAt());
        self::assertEquals('1', $entity->getAccessToken());
        self::assertEquals('111', $entity->getRefreshToken());
    }
}
