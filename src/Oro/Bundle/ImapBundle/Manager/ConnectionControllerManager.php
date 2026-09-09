<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * This class handle connection forms for IMAP
 */
class ConnectionControllerManager
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private FormFactoryInterface $formFactory,
        private SymmetricCrypterInterface $crypter,
        private ManagerRegistry $doctrine,
        private ImapConnectorFactory $imapConnectorFactory,
        private OAuthManagerRegistry $oauthManagerRegistry,
        private AuthorizationCheckerInterface $authorizationChecker,
        private OAuthTokenStorage $oauthTokenStorage,
        private string $userFormName,
        private string $userFormType,
        private string $emailMailboxFormName,
        private string $emailMailboxFormType
    ) {
    }

    /**
     * Gets a form to check connection.
     */
    public function getCheckConnectionForm(
        Request $request,
        string $formParentName,
        string $accountType
    ): FormInterface {
        $requestedAccountType = $request->get('type');
        if (null !== $requestedAccountType && $requestedAccountType !== $accountType) {
            throw new AccessDeniedException();
        }

        $data = $this->getUserEmailOrigin($request->get('id'));
        if ($data && $data->getAccountType() !== $accountType) {
            throw new AccessDeniedException();
        }

        $oauthManager = $this->oauthManagerRegistry->getManager($accountType);

        $typeClass = $oauthManager->getConnectionFormTypeClass();
        $form = $this->formFactory->create($typeClass, null, ['csrf_protection' => false]);
        $form->setData($data);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            throw new Exception('Incorrect setting for IMAP authentication');
        }

        /** @var UserEmailOrigin $origin */
        $origin = $form->getData();

        $password = $this->crypter->decryptData($origin->getPassword());

        $config = new ImapConfig(
            $origin->getImapHost(),
            $origin->getImapPort(),
            $origin->getImapEncryption(),
            $origin->getUser(),
            $password,
            $origin->getAccessToken()
        );

        $connector = $this->imapConnectorFactory->createImapConnector($config);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->doctrine->getManager();
        $manager = new ImapEmailFolderManager($connector, $entityManager, $origin);

        $emailFolders = $manager->getFolders();
        $origin->setFolders($emailFolders);

        $accountTypeModel = $this->createAccountModel($oauthManager->getType(), $origin);

        return $this->prepareForm($formParentName, $accountTypeModel);
    }

    public function getImapConnectionForm(
        string $type,
        ?string $oauthTokenHandle,
        string $formParentName,
        $originId = null
    ): FormInterface {
        $emailOrigin = new UserEmailOrigin();

        $existingOrigin = null;
        if ($originId) {
            $existingOrigin = $this->getUserEmailOrigin($originId);
        }

        // if user have existing email origin and old or new origin type is Other - use existing origin as base
        // to be able to save existing synced emails.
        if (
            $existingOrigin
            && (
                $existingOrigin->getAccountType() === AccountTypeModel::ACCOUNT_TYPE_OTHER
                || $type === AccountTypeModel::ACCOUNT_TYPE_OTHER
            )
        ) {
            $emailOrigin = $existingOrigin;
        }

        if (
            $oauthTokenHandle
            && !$this->oauthTokenStorage->applyToOrigin($oauthTokenHandle, $type, $emailOrigin)
        ) {
            throw new Exception('Invalid or expired OAuth credentials');
        }

        if ($type && ($type !== AccountTypeModel::ACCOUNT_TYPE_OTHER)) {
            $emailOrigin->setAccountType($type);
            $this->oauthManagerRegistry->getManager($type)->setOriginDefaults($emailOrigin);
        }

        $accountTypeModel = $this->createAccountModel($type, $emailOrigin);

        return $this->prepareForm($formParentName, $accountTypeModel);
    }

    private function prepareForm(string $formParentName, AccountTypeModel $accountTypeModel): ?FormInterface
    {
        $form = null;
        if ($formParentName === $this->userFormName || $formParentName === 'value') {
            $data = new User();
            $data->setImapAccountType($accountTypeModel);
            $form = $this->formFactory->createNamed(
                $this->userFormName,
                $this->userFormType,
                null,
                ['csrf_protection' => false]
            );
            $form->setData($data);
        } elseif ($formParentName === $this->emailMailboxFormName) {
            $data = new Mailbox();
            $data->setImapAccountType($accountTypeModel);
            $form = $this->formFactory->createNamed(
                $this->emailMailboxFormName,
                $this->emailMailboxFormType,
                null,
                ['csrf_protection' => false]
            );
            $form->setData($data);
        }

        return $form;
    }

    private function createAccountModel(string $type, UserEmailOrigin $oauthEmailOrigin): AccountTypeModel
    {
        $accountTypeModel = new AccountTypeModel();
        $accountTypeModel->setAccountType($type);
        $accountTypeModel->setUserEmailOrigin($oauthEmailOrigin);

        return $accountTypeModel;
    }

    private function getUserEmailOrigin(?string $id): ?UserEmailOrigin
    {
        if (!$id) {
            return null;
        }

        $origin = $this->doctrine->getRepository(UserEmailOrigin::class)->find((int)$id);
        if ($origin && !$this->isUserEmailOriginAccessGranted($origin)) {
            throw new AccessDeniedException();
        }

        return $origin;
    }

    private function isUserEmailOriginAccessGranted(UserEmailOrigin $origin): bool
    {
        // System mailbox case: the origin is linked to a Mailbox and must belong to the same organization.
        $mailbox = $origin->getMailbox();
        if (null !== $mailbox) {
            $mailboxOrganization = $mailbox->getOrganization();
            $originOrganization = $origin->getOrganization();

            // System mailbox configuration is allowed to users who can edit the mailbox itself.
            return null !== $mailboxOrganization
                && null !== $originOrganization
                && $mailboxOrganization === $originOrganization
                && $this->authorizationChecker->isGranted(BasicPermission::EDIT, $mailbox);
        }

        // Personal mailbox case: the origin is owned by a user and belongs to one of the owner's organizations.
        $owner = $origin->getOwner();
        $originOrganization = $origin->getOrganization();

        // Personal mailbox configuration is allowed to users who can configure the origin owner.
        return null !== $owner
            && $originOrganization instanceof Organization
            && $owner->hasOrganization($originOrganization)
            && $this->authorizationChecker->isGranted('CONFIGURE', $owner);
    }
}
