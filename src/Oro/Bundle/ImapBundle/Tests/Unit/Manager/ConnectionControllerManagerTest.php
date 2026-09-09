<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Oro\Bundle\ImapBundle\Mail\Storage\Office365Imap;
use Oro\Bundle\ImapBundle\Manager\ConnectionControllerManager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailGoogleOAuthManager;
use Oro\Bundle\ImapBundle\Manager\ImapEmailMicrosoftOAuthManager;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;
use Oro\Bundle\ImapBundle\Manager\OAuthTokenStorage;
use Oro\Bundle\ImapBundle\Provider\OAuthProviderInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Encoder\DefaultCrypter;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ConnectionControllerManagerTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private SymmetricCrypterInterface $crypter;
    private ManagerRegistry&MockObject $doctrine;
    private ImapConnectorFactory&MockObject $imapConnectorFactory;
    private OAuthManagerRegistry&MockObject $oauthManagerRegistry;
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private OAuthTokenStorage&MockObject $oauthTokenStorage;
    private ConnectionControllerManager $controllerManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->crypter = new DefaultCrypter('test');
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->imapConnectorFactory = $this->createMock(ImapConnectorFactory::class);
        $this->oauthManagerRegistry = $this->createMock(OAuthManagerRegistry::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->oauthTokenStorage = $this->createMock(OAuthTokenStorage::class);

        $this->controllerManager = new ConnectionControllerManager(
            $this->formFactory,
            $this->crypter,
            $this->doctrine,
            $this->imapConnectorFactory,
            $this->oauthManagerRegistry,
            $this->authorizationChecker,
            $this->oauthTokenStorage,
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

        $this->oauthTokenStorage->expects(self::once())
            ->method('applyToOrigin')
            ->with(
                'oauthTokenHandle',
                AccountTypeModel::ACCOUNT_TYPE_MICROSOFT,
                self::isInstanceOf(UserEmailOrigin::class)
            )
            ->willReturnCallback(function (string $handle, string $type, UserEmailOrigin $origin) {
                $origin->setAccessToken('accessToken');

                return true;
            });

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects(self::once())
            ->method('createNamed')
            ->with('userForm', 'userFormType', null, ['csrf_protection' => false])
            ->willReturn($form);

        $form->expects(self::once())
            ->method('setData')
            ->willReturnCallback(function (User $user) use ($expectedData, $form) {
                $user->setSalt('');
                $expectedData->setSalt('');
                self::assertEquals($expectedData, $user);

                return $form;
            });

        $resultForm = $this->controllerManager->getImapConnectionForm(
            AccountTypeModel::ACCOUNT_TYPE_MICROSOFT,
            'oauthTokenHandle',
            'userForm',
            null
        );

        self::assertEquals($form, $resultForm);
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
        $expectedOauthEmailOrigin = clone $oauthEmailOrigin;
        $owner = new User();
        $organization = new Organization();
        $owner->addOrganization($organization);
        $oauthEmailOrigin->setOwner($owner);
        $oauthEmailOrigin->setOrganization($organization);

        $accountTypeModel = new AccountTypeModel();
        $accountTypeModel->setAccountType(AccountTypeModel::ACCOUNT_TYPE_GMAIL);
        $accountTypeModel->setUserEmailOrigin($expectedOauthEmailOrigin);

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

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CONFIGURE', $owner)
            ->willReturn(true);

        $this->oauthManagerRegistry->expects(self::once())
            ->method('getManager')
            ->with(AccountTypeModel::ACCOUNT_TYPE_GMAIL)
            ->willReturn(new ImapEmailGoogleOAuthManager(
                $this->doctrine,
                $this->createMock(OAuthProviderInterface::class),
                $this->createMock(ConfigManager::class)
            ));

        $this->oauthTokenStorage->expects(self::once())
            ->method('applyToOrigin')
            ->with(
                'oauthTokenHandle',
                AccountTypeModel::ACCOUNT_TYPE_GMAIL,
                self::isInstanceOf(UserEmailOrigin::class)
            )
            ->willReturnCallback(function (string $handle, string $type, UserEmailOrigin $origin) {
                $origin->setAccessToken('accessToken');

                return true;
            });

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects(self::once())
            ->method('createNamed')
            ->with('userForm', 'userFormType', null, ['csrf_protection' => false])
            ->willReturn($form);

        $form->expects(self::once())
            ->method('setData')
            ->willReturnCallback(function (User $user) use ($expectedData, $form) {
                $user->setSalt('');
                $expectedData->setSalt('');
                self::assertEquals($expectedData, $user);

                return $form;
            });

        $resultForm = $this->controllerManager->getImapConnectionForm(
            AccountTypeModel::ACCOUNT_TYPE_GMAIL,
            'oauthTokenHandle',
            'userForm',
            12
        );

        self::assertEquals($form, $resultForm);
    }

    public function testGetCheckConnectionFormRejectsForeignOriginBeforeCreatingForm(): void
    {
        $owner = new User();
        $organization = new Organization();
        $owner->addOrganization($organization);
        $origin = new UserEmailOrigin();
        $origin->setOwner($owner);
        $origin->setOrganization($organization);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($origin);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(UserEmailOrigin::class)
            ->willReturn($repository);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CONFIGURE', $owner)
            ->willReturn(false);
        $this->oauthManagerRegistry->expects(self::never())
            ->method('getManager');
        $this->formFactory->expects(self::never())
            ->method('create');
        $this->imapConnectorFactory->expects(self::never())
            ->method('createImapConnector');

        $this->expectException(AccessDeniedException::class);

        $this->controllerManager->getCheckConnectionForm(
            new Request(request: ['id' => '42']),
            'userForm',
            AccountTypeModel::ACCOUNT_TYPE_GMAIL
        );
    }

    /**
     * @dataProvider oauthAccountTypeProvider
     */
    public function testGetCheckConnectionFormRejectsMissingNamedFormRoot(string $accountType): void
    {
        $owner = new User();
        $organization = new Organization();
        $owner->addOrganization($organization);
        $origin = new UserEmailOrigin();
        $origin->setOwner($owner);
        $origin->setOrganization($organization);
        $origin->setAccountType($accountType);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($origin);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(UserEmailOrigin::class)
            ->willReturn($repository);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CONFIGURE', $owner)
            ->willReturn(true);

        $oauthManager = $this->createMock(OAuthManagerInterface::class);
        $oauthManager->expects(self::once())
            ->method('getConnectionFormTypeClass')
            ->willReturn('connectionFormType');
        $this->oauthManagerRegistry->expects(self::once())
            ->method('getManager')
            ->with($accountType)
            ->willReturn($oauthManager);

        $form = $this->createMock(FormInterface::class);
        $this->formFactory->expects(self::once())
            ->method('create')
            ->with('connectionFormType', null, ['csrf_protection' => false])
            ->willReturn($form);
        $form->expects(self::once())
            ->method('setData')
            ->with($origin)
            ->willReturn($form);
        $form->expects(self::once())
            ->method('handleRequest')
            ->willReturn($form);
        $form->expects(self::once())
            ->method('isSubmitted')
            ->willReturn(false);
        $form->expects(self::never())
            ->method('getData');
        $this->imapConnectorFactory->expects(self::never())
            ->method('createImapConnector');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Incorrect setting for IMAP authentication');

        $this->controllerManager->getCheckConnectionForm(
            new Request(request: ['id' => '42', 'formParentName' => 'userForm']),
            'userForm',
            $accountType
        );
    }

    public function oauthAccountTypeProvider(): array
    {
        return [
            'Gmail' => [AccountTypeModel::ACCOUNT_TYPE_GMAIL],
            'Microsoft' => [AccountTypeModel::ACCOUNT_TYPE_MICROSOFT]
        ];
    }

    public function testGetCheckConnectionFormRejectsOriginFromAnotherProviderContext(): void
    {
        $owner = new User();
        $organization = new Organization();
        $owner->addOrganization($organization);

        $origin = new UserEmailOrigin();
        $origin->setOwner($owner);
        $origin->setOrganization($organization);
        $origin->setAccountType(AccountTypeModel::ACCOUNT_TYPE_GMAIL);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($origin);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(UserEmailOrigin::class)
            ->willReturn($repository);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with('CONFIGURE', $owner)
            ->willReturn(true);
        $this->oauthManagerRegistry->expects(self::never())
            ->method('getManager');
        $this->imapConnectorFactory->expects(self::never())
            ->method('createImapConnector');

        $this->expectException(AccessDeniedException::class);

        $this->controllerManager->getCheckConnectionForm(
            new Request(request: ['id' => '42']),
            'userForm',
            AccountTypeModel::ACCOUNT_TYPE_MICROSOFT
        );
    }

    public function testGetCheckConnectionFormRejectsMailboxOriginWithoutEditPermission(): void
    {
        $organization = new Organization();
        $mailbox = new Mailbox();
        $mailbox->setOrganization($organization);

        $origin = new UserEmailOrigin();
        $origin->setOrganization($organization);
        $origin->setMailbox($mailbox);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())
            ->method('find')
            ->with(42)
            ->willReturn($origin);

        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(UserEmailOrigin::class)
            ->willReturn($repository);
        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with(BasicPermission::EDIT, $mailbox)
            ->willReturn(false);
        $this->oauthManagerRegistry->expects(self::never())
            ->method('getManager');
        $this->imapConnectorFactory->expects(self::never())
            ->method('createImapConnector');

        $this->expectException(AccessDeniedException::class);

        $this->controllerManager->getCheckConnectionForm(
            new Request(request: ['id' => '42']),
            'mailboxForm',
            AccountTypeModel::ACCOUNT_TYPE_GMAIL
        );
    }
}
