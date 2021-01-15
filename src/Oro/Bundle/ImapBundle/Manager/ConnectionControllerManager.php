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
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class handle connection forms for IMAP
 */
class ConnectionControllerManager
{
    /** @var FormFactory */
    protected $formFactory;

    /** @var SymmetricCrypterInterface */
    protected $crypter;

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ImapConnectorFactory */
    protected $imapConnectorFactory;

    /** @var string */
    protected $userFormName;

    /** @var string */
    protected $userFormType;

    /** @var string */
    protected $emailMailboxFormName;

    /** @var string */
    protected $emailMailboxFormType;

    /** @var OAuth2ManagerRegistry */
    protected $oauthManagerRegistry;

    /** @var string */
    protected $type;

    /**
     * @param FormFactory $formFactory
     * @param SymmetricCrypterInterface $crypter
     * @param ManagerRegistry $doctrineHelper
     * @param ImapConnectorFactory $imapConnectorFactory
     * @param OAuth2ManagerRegistry $oauthManagerRegistry
     * @param string $userFormName
     * @param $userFormType
     * @param string $emailMailboxFormName
     * @param string $emailMailboxFormType
     */
    public function __construct(
        FormFactory $formFactory,
        SymmetricCrypterInterface $crypter,
        ManagerRegistry $doctrineHelper,
        ImapConnectorFactory $imapConnectorFactory,
        OAuth2ManagerRegistry $oauthManagerRegistry,
        $userFormName,
        $userFormType,
        $emailMailboxFormName,
        $emailMailboxFormType
    ) {
        $this->formFactory = $formFactory;
        $this->crypter = $crypter;
        $this->doctrine = $doctrineHelper;
        $this->imapConnectorFactory = $imapConnectorFactory;
        $this->oauthManagerRegistry = $oauthManagerRegistry;
        $this->userFormName = $userFormName;
        $this->userFormType = $userFormType;
        $this->emailMailboxFormName = $emailMailboxFormName;
        $this->emailMailboxFormType = $emailMailboxFormType;
    }

    /**
     * Returns check connection form instance
     *
     * @param Request $request
     * @param string $formParentName
     * @return FormInterface
     */
    public function getCheckConnectionForm(Request $request, string $formParentName): FormInterface
    {
        $id = $request->get('id', null);
        /** @var UserEmailOrigin $data */
        $data = $id ? $this->doctrine->getRepository(UserEmailOrigin::class)->find($id) : null;

        $type = $data ? $data->getAccountType() : $request->get('type');
        $oauthManager = $this->oauthManagerRegistry->getManager($type);

        $typeClass = $oauthManager->getConnectionFormTypeClass();
        $form = $this->formFactory->create($typeClass, null, ['csrf_protection' => false]);
        $form->setData($data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            throw new Exception("Incorrect setting for IMAP authentication");
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

    /**
     * @param Request $request
     * @param string  $formParentName
     *
     * @return FormInterface
     * @deprecated Pleas use \Oro\Bundle\ImapBundle\Manager\ConnectionControllerManager::getCheckConnectionForm()
     *              with certain type taken from request
     */
    public function getCheckGmailConnectionForm($request, $formParentName)
    {
        return $this->getCheckConnectionForm($request, $formParentName);
    }

    /**
     * @param string $type
     * @param string|null $accessToken
     * @param string $formParentName
     *
     * @return FormInterface
     */
    public function getImapConnectionForm($type, $accessToken, $formParentName)
    {
        $oauthEmailOrigin = new UserEmailOrigin();
        $oauthEmailOrigin->setAccessToken($accessToken);

        if ($type && ($type !== AccountTypeModel::ACCOUNT_TYPE_OTHER)) {
            $oauthEmailOrigin->setAccountType($type);
            $this->oauthManagerRegistry->getManager($type)->setOriginDefaults($oauthEmailOrigin);
        }

        $accountTypeModel = $this->createAccountModel($type, $oauthEmailOrigin);

        return $this->prepareForm($formParentName, $accountTypeModel);
    }

    /**
     * Get oauth2 access token by security code
     *
     * @param string $code
     * @param string $type
     * @return array
     */
    public function getAccessToken($code, $type)
    {
        $manager = $this->oauthManagerRegistry->getManager($type);
        $accessTokenData = $manager->getAccessTokenDataByAuthCode($code);
        try {
            $userInfo = $manager->getUserInfo($accessTokenData);
            $response = [
                'access_token' => $accessTokenData->getAccessToken(),
                'refresh_token' => $accessTokenData->getRefreshToken(),
                'expires_in' => $accessTokenData->getExpiresIn(),
                'email_address' => $userInfo->getEmail()
            ];
        } catch (\HWI\Bundle\OAuthBundle\OAuth\Exception\HttpTransportException $exc) {
            $response = [ 'error' => $exc->getMessage() ];
        }

        return $response;
    }

    /**
     * @param $formParentName
     * @param $accountTypeModel
     * @return null|\Symfony\Component\Form\Form|FormInterface
     */
    protected function prepareForm($formParentName, $accountTypeModel)
    {
        $form = null;
        if ($formParentName === $this->userFormName || $formParentName === 'value') {
            $data = $user = new User();
            $data->setImapAccountType($accountTypeModel);
            $form = $this->formFactory->createNamed(
                $this->userFormName,
                $this->userFormType,
                null,
                ['csrf_protection' => false]
            );
            $form->setData($data);
        } elseif ($formParentName === $this->emailMailboxFormName) {
            $data = $user = new Mailbox();
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
    protected function createAccountModel($type, $oauthEmailOrigin)
    {
        $accountTypeModel = new AccountTypeModel();
        $accountTypeModel->setAccountType($type);
        $accountTypeModel->setUserEmailOrigin($oauthEmailOrigin);

        return $accountTypeModel;
    }
}
