<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Oro\Bundle\ImapBundle\Mail\Storage\GmailImap;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Class ConnectionManager
 *
 * @package Oro\Bundle\ImapBundle\Manager
 */
class ConnectionControllerManager
{
    /** @var FormFactory */
    protected $formFactory;

    /** @var Mcrypt */
    protected $mcrypt;

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

    /** @var ImapEmailGoogleOauth2Manager */
    protected $imapEmailGoogleOauth2Manager;

    /**
     * @param FormFactory $formFactory
     * @param Mcrypt $mcrypt
     * @param ManagerRegistry $doctrineHelper
     * @param ImapConnectorFactory $imapConnectorFactory
     * @param ImapEmailGoogleOauth2Manager $imapEmailGoogleOauth2Manager
     * @param string $userFormName
     * @param $userFormType
     * @param string $emailMailboxFormName
     * @param string $emailMailboxFormType
     */
    public function __construct(
        FormFactory $formFactory,
        Mcrypt $mcrypt,
        ManagerRegistry $doctrineHelper,
        ImapConnectorFactory $imapConnectorFactory,
        ImapEmailGoogleOauth2Manager $imapEmailGoogleOauth2Manager,
        $userFormName,
        $userFormType,
        $emailMailboxFormName,
        $emailMailboxFormType
    ) {
        $this->formFactory = $formFactory;
        $this->mcrypt = $mcrypt;
        $this->doctrine = $doctrineHelper;
        $this->imapConnectorFactory = $imapConnectorFactory;
        $this->imapEmailGoogleOauth2Manager = $imapEmailGoogleOauth2Manager;
        $this->userFormName = $userFormName;
        $this->userFormType = $userFormType;
        $this->emailMailboxFormName = $emailMailboxFormName;
        $this->emailMailboxFormType = $emailMailboxFormType;
    }

    /**
     * @param Request $request
     * @param string  $formParentName
     *
     * @return FormInterface
     */
    public function getCheckGmailConnectionForm($request, $formParentName)
    {
        $data = null;
        $id = $request->get('id', null);
        if (!empty($id)) {
            $data = $this->doctrine->getRepository('OroImapBundle:UserEmailOrigin')->find($id);
        }

        $form = $this->formFactory->create('oro_imap_configuration_gmail', null, ['csrf_protection' => false]);
        $form->setData($data);
        $form->submit($request);

        if (!$form->isValid()) {
            throw new Exception("Incorrect setting for IMAP authentication");
        }

        /** @var UserEmailOrigin $origin */
        $origin = $form->getData();

        $password = $this->mcrypt->decryptData($origin->getPassword());

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

        $accountTypeModel = $this->createAccountModel(AccountTypeModel::ACCOUNT_TYPE_GMAIL, $origin);

        return $this->prepareForm($formParentName, $accountTypeModel);
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

        if ($type === AccountTypeModel::ACCOUNT_TYPE_GMAIL) {
            $oauthEmailOrigin->setImapHost(GmailImap::DEFAULT_GMAIL_HOST);
            $oauthEmailOrigin->setImapPort(GmailImap::DEFAULT_GMAIL_PORT);
        }

        $accountTypeModel = $this->createAccountModel($type, $oauthEmailOrigin);

        return $this->prepareForm($formParentName, $accountTypeModel);
    }

    /**
     * Get oauth2 access token by security code
     *
     * @param $code
     *
     * @return array
     */
    public function getAccessToken($code)
    {
        $accessToken = $this->imapEmailGoogleOauth2Manager->getAccessTokenByAuthCode($code);
        $userInfo = $this->imapEmailGoogleOauth2Manager->getUserInfo($accessToken['access_token']);
        $userInfoResponse = $userInfo->getResponse();
        if (array_key_exists('error', $userInfoResponse)) {
            $response = $userInfoResponse['error'];
        } else {
            $response = [
                'access_token' => $accessToken['access_token'],
                'refresh_token' => $accessToken['refresh_token'],
                'expires_in' => $accessToken['expires_in'],
                'email_address' => $userInfo->getEmail()
            ];
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
