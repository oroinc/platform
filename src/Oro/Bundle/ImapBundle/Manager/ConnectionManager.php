<?php

namespace Oro\Bundle\ImapBundle\Manager;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Form\Model\AccountTypeModel;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;

/**
 * Class ConnectionManager
 *
 * @package Oro\Bundle\ImapBundle\Manager
 */
class ConnectionManager
{
    /**
     * @var FormInterface
     */
    protected $formUser;

    /**
     * @var FormFactory
     */
    protected $FormFactory;

    public function __construct(FormInterface $formUser, FormFactory $FormFactory)
    {
        $this->formUser = $formUser;
        $this->FormFactory = $FormFactory;
    }

    /**
     * @param string|null $accessToken
     *
     * @return mixed
     */
    public function getFormCheckGmailConnection($accessToken)
    {
        $oauth = new UserEmailOrigin();
        $oauth->setAccessToken($accessToken);

        $form = $this->FormFactory->create('oro_imap_configuration_gmail', null, ['csrf_protection' => false]);
        $form->setData($oauth);

        /** @var UserEmailOrigin $origin */
        $origin = $form->getData();

        $folder = new EmailFolder();
        $folder->setName('INBOx');
        $folder->setFullName('INBOx');
        $folder->setType('inbox');
        $folder->setOrigin($origin);
        $origin->addFolder($folder);

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
