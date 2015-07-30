<?php

namespace Oro\Bundle\EmailBundle\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Form\Type\MailboxType;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class MailboxHandler
{
    /** @var FormInterface */
    private $form;
    /** @var Request */
    private $request;
    /** @var Registry */
    protected $doctrine;
    /** @var AclManager */
    private $aclManager;
    /** @var MailboxProcessStorage */
    private $mailboxProcessStorage;

    /**
     * @param FormInterface         $form
     * @param Request               $request
     * @param Registry              $doctrine
     * @param AclManager            $aclManager
     * @param MailboxProcessStorage $mailboxProcessStorage
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        Registry $doctrine,
        AclManager $aclManager,
        MailboxProcessStorage $mailboxProcessStorage
    ) {
        $this->doctrine = $doctrine;
        $this->form = $form;
        $this->request = $request;
        $this->aclManager = $aclManager;
        $this->mailboxProcessStorage = $mailboxProcessStorage;
    }

    /**
     * Process form.
     *
     * @param Mailbox $mailbox
     *
     * @return bool True on success.
     */
    public function process(Mailbox $mailbox)
    {
        $this->form->setData($mailbox);

        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
            if ($this->request->get(MailboxType::RELOAD_MARKER, false)) {
                $this->processReload();
            } else {
                $this->form->submit($this->request);

                if ($this->form->isValid()) {
                    $mailbox = $this->form->getData();
                    $this->getEntityManager()->persist($mailbox);

                    $this->setPermissions(
                        $mailbox,
                        $this->form->get('allowedUsers')->getData(),
                        $this->form->get('allowedRoles')->getData()
                    );

                    return true;
                }
            }
        }

        return false;
    }

    protected function processReload()
    {
        $type = $this->form->get('processType')->getViewData();
        /** @var Mailbox $data */
        $data = $this->form->getData();

        if (!empty($type)) {
            $processorEntity = $this->mailboxProcessStorage->getNewSettingsEntity($type);
            $data->setProcessSettings($processorEntity);
        } else {
            $data->setProcessSettings(null);
        }

        $this->form->setData($data);
    }

    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param Mailbox $mailbox
     * @param User[]  $users
     * @param Role[]  $roles
     */
    protected function setPermissions(Mailbox $mailbox, array $users = [], array $roles = [])
    {
        $this->grandPermissionTo($mailbox, $users);
        $this->grandPermissionTo($mailbox, $roles);

        $this->aclManager->flush();
    }

    /**
     * @param Mailbox $mailbox
     * @param array   $granted
     */
    protected function grandPermissionTo(Mailbox $mailbox, array $granted)
    {
        foreach ($granted as $subject) {
            $this->aclManager->setPermission(
                $this->aclManager->getSid($subject),
                $this->aclManager->getOid($mailbox),
                $this->aclManager->getMaskBuilder(
                    $this->aclManager->getOid($mailbox),
                    'VIEW'
                )->get()
            );
        }
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrine->getManager();
    }
}
