<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Oro\Bundle\ImapBundle\Mail\Storage\Office365Imap;
use Oro\Bundle\ImapBundle\Manager\ConnectionControllerManager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOAuthManager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailMicrosoftOAuthManager;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class ConnectionControllerManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject  */
    private $formFactory;

    /** @var SymmetricCrypterInterface */
    private $crypter;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject  */
    private ManagerRegistry $doctrine;

    /** @var ImapConnectorFactory|\PHPUnit\Framework\MockObject\MockObject  */
    private ImapConnectorFactory $imapConnectorFactory;

    /** @var OAuthManagerRegistry|\PHPUnit\Framework\MockObject\MockObject  */
    private OAuthManagerRegistry $oauthManagerRegistry;

    private ConnectionControllerManager $controllerManager;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->crypter = new DefaultCrypter('test');
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->imapConnectorFactory = $this->createMock(ImapConnectorFactory::class);
        $this->oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);

        $this->controllerManager = new ConnectionControllerManager(
            $this->formFactory,
            $this->crypter,
            $this->doctrine,
            $this->imapConnectorFactory,
            $this->oauthManagerRegistry,
            'userForm',
            'userFormType',
            'mailboxForm',
            'mailboxFormType',
        );
    }

    public function testGetImapConnectionFormWithoutExistingOriginAndMsFormTypeOnUserPage(): void
    {
        $oauthEmailOrigin = new UserEmailOrigin();
        $oauthEmailOrigin->setAccessToken('accessToken');
        $oauthEmailOrigin->setAccountType(AccountTypeModel::ACCOUNT_TYPE_MICROSOFT);
        $oauthEmailOrigin->setImapHost(Office365Imap::DEFAULT_IMAP_HOST);
        $oauthEmailOrigin->setImapPort(Office365Imap::DEFAULT_IMAP_PORT);
        $oauthEmailOrigin->setImapEncryption(Office365Imap::DEFAULT_IMAP_ENCRYPTION);
        $oauthEmailOrigin->setSmtpHost(Office365Imap::DEFAULT_SMTP_HOST);
        $oauthEmailOrigin->setSmtpPort(Office365Imap::DEFAULT_SMTP_PORT);
        $oauthEmailOrigin->setSmtpEncryption(Office365Imap::DEFAULT_SMTP_ENCRYPTION);

        $accountTypeModel = new AccountTypeModel();
        $accountTypeModel->setAccountType(AccountTypeModel::ACCOUNT_TYPE_MICROSOFT);
        $accountTypeModel->setUserEmailOrigin($oauthEmailOrigin);

        $expectedData = new User();
        $expectedData->setImapAccountType($accountTypeModel);

        $this->doctrine->expects(self::never())
            ->method('getRepository');

        $this->oauthManagerRegistry->expects(self::once())
            ->method('getManager')
            ->with(AccountTypeModel::ACCOUNT_TYPE_MICROSOFT)
            ->willReturn(new ImapEmailMicrosoftOAuthManager(
                $this->doctrine,
                $this->createMock(OAuthProviderInterface::class),
                $this->createMock(ConfigManager::class)
            ));

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects(self::once())
            ->method('createNamed')
            ->with('userForm', 'userFormType', null, ['csrf_protection' => false])
            ->willReturn($form);

        $form->expects(self::once())
            ->method('setData')
            ->willReturnCallback(function (User $user) use ($expectedData) {
                $user->setSalt('');
                $expectedData->setSalt('');
                self::assertEquals($expectedData, $user);

                return;
            });

        $resultForm = $this->controllerManager->getImapConnectionForm(
            AccountTypeModel::ACCOUNT_TYPE_MICROSOFT,
            'accessToken',
            'userForm',
            null
        );

        self::assertSame($form, $resultForm);
    }

    public function testGetImapConnectionFormWitExistingOriginAndGoogleFormTypeOnUserPage(): void
    {
        $oauthEmailOrigin = new UserEmailOrigin();
        $oauthEmailOrigin->setAccessToken('accessToken');
        $oauthEmailOrigin->setAccountType(AccountTypeModel::ACCOUNT_TYPE_GMAIL);
        $oauthEmailOrigin->setImapHost(GmailImap::DEFAULT_GMAIL_HOST);
        $oauthEmailOrigin->setImapPort(GmailImap::DEFAULT_GMAIL_PORT);
        $oauthEmailOrigin->setImapEncryption(GmailImap::DEFAULT_GMAIL_SSL);
        $oauthEmailOrigin->setSmtpHost(GmailImap::DEFAULT_GMAIL_SMTP_HOST);
        $oauthEmailOrigin->setSmtpPort(GmailImap::DEFAULT_GMAIL_SMTP_PORT);
        $oauthEmailOrigin->setSmtpEncryption(GmailImap::DEFAULT_GMAIL_SMTP_SSL);

        $accountTypeModel = new AccountTypeModel();
        $accountTypeModel->setAccountType(AccountTypeModel::ACCOUNT_TYPE_GMAIL);
        $accountTypeModel->setUserEmailOrigin($oauthEmailOrigin);

        $expectedData = new User();
        $expectedData->setImapAccountType($accountTypeModel);

        $repo = $this->createMock(ObjectRepository::class);
        $repo->expects(self::once())
            ->method('find')
            ->with(12)
            ->willReturn($oauthEmailOrigin);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(UserEmailOrigin::class)
            ->willReturn($repo);

        $this->oauthManagerRegistry->expects(self::once())
            ->method('getManager')
            ->with(AccountTypeModel::ACCOUNT_TYPE_GMAIL)
            ->willReturn(new ImapEmailGoogleOAuthManager(
                $this->doctrine,
                $this->createMock(OAuthProviderInterface::class),
                $this->createMock(ConfigManager::class)
            ));

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects(self::once())
            ->method('createNamed')
            ->with('userForm', 'userFormType', null, ['csrf_protection' => false])
            ->willReturn($form);

        $form->expects(self::once())
            ->method('setData')
            ->willReturnCallback(function (User $user) use ($expectedData) {
                $user->setSalt('');
                $expectedData->setSalt('');
                self::assertEquals($expectedData, $user);

                return;
            });

        $resultForm = $this->controllerManager->getImapConnectionForm(
            AccountTypeModel::ACCOUNT_TYPE_GMAIL,
            'accessToken',
            'userForm',
            12
        );

        self::assertSame($form, $resultForm);
    }
}
