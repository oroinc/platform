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
    /** @var FormInterface */
    protected $formUser;

    /** @var FormFactory */
    protected $FormFactory;

    /** @var Mcrypt */
    protected $mcrypt;

    /** @var Registry */
    protected $doctrine;

    /** @var ImapConnectorFactory */
    protected $imapConnectorFactory;

    public function __construct(
        FormInterface $formUser,
        FormFactory $FormFactory,
        Mcrypt $mcrypt,
        Registry $doctrineHelper,
        ImapConnectorFactory $imapConnectorFactory
    ) {
        $this->formUser = $formUser;
        $this->FormFactory = $FormFactory;
        $this->mcrypt = $mcrypt;
        $this->doctrine = $doctrineHelper;
        $this->imapConnectorFactory = $imapConnectorFactory;
    }

    /**
     * @param $request
     * $param sting $formParentName
     * @return FormInterface
     */
    public function getFormCheckGmailConnection($request, $formParentName)
    {
        $form = $this->FormFactory->create('oro_imap_configuration_gmail', null, ['csrf_protection' => false]);
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

        $accountTypeModel = new AccountTypeModel();
        $accountTypeModel->setAccountType(AccountTypeModel::ACCOUNT_TYPE_GMAIL);
        $accountTypeModel->setUserEmailOrigin($origin);


        $form = null;
        switch ($formParentName) {
            case 'oro_user_user_form':
                $data = $user = new User();
                $data->setImapAccountType($accountTypeModel);
                $this->formUser->setData($data);
                $form = $this->formUser;
                break;
            case 'oro_email_mailbox':
                $formAlias = 'oro_email_mailbox';
                $data = $user = new Mailbox();
                $data->setImapAccountType($accountTypeModel);
                $form = $this->FormFactory->create($formAlias, null, ['csrf_protection' => false]);
                $form->setData($data);
                break;
        }

        return $form;
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

        $accountTypeModel = new AccountTypeModel();
        $accountTypeModel->setAccountType($type);
        $accountTypeModel->setUserEmailOrigin($oauthEmailOrigin);

        $form = null;
        switch ($formParentName) {
            case 'oro_user_user_form':
                $data = $user = new User();
                $data->setImapAccountType($accountTypeModel);
                $this->formUser->setData($data);
                $form = $this->formUser;
                break;
            case 'oro_email_mailbox':
                $formAlias = 'oro_email_mailbox';
                $data = $user = new Mailbox();
                $data->setImapAccountType($accountTypeModel);
                $form = $this->FormFactory->create($formAlias, null, ['csrf_protection' => false]);
                $form->setData($data);
                break;
        }

        return $form;
    }
}
