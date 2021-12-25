<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class handle connection forms for IMAP
 */
class ConnectionControllerManager
{
    private FormFactoryInterface $formFactory;
    private SymmetricCrypterInterface $crypter;
    private ManagerRegistry $doctrine;
    private ImapConnectorFactory $imapConnectorFactory;
    private OAuthManagerRegistry $oauthManagerRegistry;
    private string $userFormName;
    private string $userFormType;
    private string $emailMailboxFormName;
    private string $emailMailboxFormType;

    public function __construct(
        FormFactoryInterface $formFactory,
        SymmetricCrypterInterface $crypter,
        ManagerRegistry $doctrine,
        ImapConnectorFactory $imapConnectorFactory,
        OAuthManagerRegistry $oauthManagerRegistry,
        string $userFormName,
        string $userFormType,
        string $emailMailboxFormName,
        string $emailMailboxFormType
    ) {
        $this->formFactory = $formFactory;
        $this->crypter = $crypter;
        $this->doctrine = $doctrine;
        $this->imapConnectorFactory = $imapConnectorFactory;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
        $this->userFormName = $userFormName;
        $this->userFormType = $userFormType;
        $this->emailMailboxFormName = $emailMailboxFormName;
        $this->emailMailboxFormType = $emailMailboxFormType;
    }

    /**
     * Gets a form to check connection.
     */
    public function getCheckConnectionForm(Request $request, string $formParentName): FormInterface
    {
        $type = $request->get('type');
        $data = $this->getUserEmailOrigin($request->get('id'));
        if (null === $type && $data) {
            $type = $data->getAccountType();
        }

        $oauthManager = $this->oauthManagerRegistry->getManager($type);

        $typeClass = $oauthManager->getConnectionFormTypeClass();
        $form = $this->formFactory->create($typeClass, null, ['csrf_protection' => false]);
        $form->setData($data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
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
        /** @var EntityManager $entityManager */
        $entityManager = $this->doctrine->getManager();
        $manager = new ImapEmailFolderManager($connector, $entityManager, $origin);

        $emailFolders = $manager->getFolders();
        $origin->setFolders($emailFolders);

        $accountTypeModel = $this->createAccountModel($oauthManager->getType(), $origin);

        return $this->prepareForm($formParentName, $accountTypeModel);
    }

    public function getImapConnectionForm(
        string $type,
        ?string $accessToken,
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
        if ($existingOrigin
            && (
                $existingOrigin->getAccountType() === AccountTypeModel::ACCOUNT_TYPE_OTHER
                || $type === AccountTypeModel::ACCOUNT_TYPE_OTHER
            )
        ) {
            $emailOrigin = $existingOrigin;
        }

        $emailOrigin->setAccessToken($accessToken);

        if ($type && ($type !== AccountTypeModel::ACCOUNT_TYPE_OTHER)) {
            $emailOrigin->setAccountType($type);
            $this->oauthManagerRegistry->getManager($type)->setOriginDefaults($emailOrigin);
        }

        $accountTypeModel = $this->createAccountModel($type, $emailOrigin);

        return $this->prepareForm($formParentName, $accountTypeModel);
    }

    /**
     * @param $formParentName
     * @param $accountTypeModel
     * @return FormInterface|null
     */
    private function prepareForm($formParentName, $accountTypeModel)
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

    /**
     * @param $type
     * @param $oauthEmailOrigin
     * @return AccountTypeModel
     */
    private function createAccountModel($type, $oauthEmailOrigin)
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

        return $this->doctrine->getRepository(UserEmailOrigin::class)->find((int)$id);
    }
}
