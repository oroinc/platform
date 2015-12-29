<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\ImapBundle\Connector\ImapConnectorFactory;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Connector\ImapConfig;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Class ConnectionManager
 *
 * @package Oro\Bundle\ImapBundle\Manager
 */
class ConnectionManager
{
    /** @var FormInterface */
    protected $formUser;

    /** @var FormFactory */
    protected $FormFactory;

    /** @var Mcrypt */
    protected $mcrypt;

    /** @var Registry */
    protected $doctrineHelper;

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
        $this->doctrineHelper = $doctrineHelper;
        $this->imapConnectorFactory = $imapConnectorFactory;
    }

    /**
     * @param $request
     *
     * @return FormInterface
     */
    public function getFormCheckGmailConnection($request)
    {
        $form = $this->FormFactory->create('oro_imap_configuration_gmail', null, ['csrf_protection' => false]);
        $form->submit($request);

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
        $this->manager = new ImapEmailFolderManager(
            $connector,
            $this->doctrineHelper->getEntityManager(),
            $origin
        );

        $emailFolders = $this->manager->getFolders();
        $origin->setFolders($emailFolders);

        $aacountType = new AccountTypeModel();
        $aacountType->setAccountType('Gmail');
        $aacountType->setImapGmailConfiguration($origin);

        $user = new User();
        $user->setImapAccountType($aacountType);
        $this->formUser->setData($user);

        return $this->formUser;
    }

    /**
     * @param string $type
     * @param string|null $accessToken
     *
     * @return FormInterface
     */
    public function getFormGmailConnect($type, $accessToken)
    {
        $oauthEmailOrigin = new UserEmailOrigin();
        $oauthEmailOrigin->setAccessToken($accessToken);

        $accountTypeModel = new AccountTypeModel();
        $accountTypeModel->setAccountType($type);
        $accountTypeModel->setImapGmailConfiguration($oauthEmailOrigin);

        $user = new User();
        $user->setImapAccountType($accountTypeModel);

        $this->formUser->setData($user);

        return $this->formUser;
    }
}
