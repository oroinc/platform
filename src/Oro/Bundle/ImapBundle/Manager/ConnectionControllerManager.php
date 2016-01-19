<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;

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
    const ORO_USER_USER_FORM = 'oro_user_user_form';
    const ORO_EMAIL_MAILBOX = 'oro_email_mailbox';

    /** @var FormInterface */
    protected $formUser;

    /** @var FormFactory */
    protected $formFactory;

    /** @var Mcrypt */
    protected $mcrypt;

    /** @var Registry */
    protected $doctrine;

    /** @var ImapConnectorFactory */
    protected $imapConnectorFactory;

    /**
     * @param FormInterface $formUser
     * @param FormFactory $formFactory
     * @param Mcrypt $mcrypt
     * @param Registry $doctrineHelper
     * @param ImapConnectorFactory $imapConnectorFactory
     */
    public function __construct(
        FormInterface $formUser,
        FormFactory $formFactory,
        Mcrypt $mcrypt,
        Registry $doctrineHelper,
        ImapConnectorFactory $imapConnectorFactory
    ) {
        $this->formUser = $formUser;
        $this->formFactory = $formFactory;
        $this->mcrypt = $mcrypt;
        $this->doctrine = $doctrineHelper;
        $this->imapConnectorFactory = $imapConnectorFactory;
    }

    /**
     * @param $request
     * $param sting $formParentName
     * @return FormInterface
     */
    public function getCheckGmailConnectionForm($request, $formParentName)
    {
        $form = $this->formFactory->create('oro_imap_configuration_gmail', null, ['csrf_protection' => false]);
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
        $manager = new ImapEmailFolderManager(
            $connector,
            $this->doctrine->getEntityManager(),
            $origin
        );

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
     * @param $formParentName
     * @param $accountTypeModel
     * @return null|\Symfony\Component\Form\Form|FormInterface
     */
    protected function prepareForm($formParentName, $accountTypeModel)
    {
        $form = null;
        if ($formParentName === self::ORO_USER_USER_FORM) {
            $data = $user = new User();
            $data->setImapAccountType($accountTypeModel);
            $this->formUser->setData($data);
            $form = $this->formUser;
        } elseif ($formParentName === self::ORO_EMAIL_MAILBOX) {
            $data = $user = new Mailbox();
            $data->setImapAccountType($accountTypeModel);
            $form = $this->formFactory->create(self::ORO_EMAIL_MAILBOX, null, ['csrf_protection' => false]);
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
