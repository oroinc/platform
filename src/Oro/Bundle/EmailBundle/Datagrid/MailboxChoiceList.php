<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class MailboxChoiceList
{
    /** @var Registry */
    private $doctrine;

    /** @var SecurityFacade */
    private $securityFacade;

    /** @var MailboxManager */
    private $mailboxManager;

    /**
     * @param Registry       $doctrine
     * @param SecurityFacade $securityFacade
     * @param MailboxManager $mailboxManager
     */
    public function __construct(Registry $doctrine, SecurityFacade $securityFacade, MailboxManager $mailboxManager)
    {
        $this->doctrine = $doctrine;
        $this->securityFacade = $securityFacade;
        $this->mailboxManager = $mailboxManager;
    }

    /**
     * Returns array of mailbox choices.
     *
     * @return array
     */
    public function getChoiceList()
    {
        /** @var Mailbox[] $systemMailboxes */
        $systemMailboxes = $this->mailboxManager->findAvailableMailboxes(
            $this->securityFacade->getLoggedUser(),
            $this->getOrganization()
        );
        $origins = $this->mailboxManager->findAvailableOrigins(
            $this->securityFacade->getLoggedUser(),
            $this->getOrganization()
        );

        $choiceList = [];
        foreach ($origins as $origin) {
            $mailbox = $origin->getMailboxName();
            if (count($origin->getFolders()) > 0) {
                $choiceList[$origin->getId()] = str_replace('@', '\@', $mailbox);
            }
        }
        foreach ($systemMailboxes as $mailbox) {
            if ($mailbox->getOrigin() !== null) {
                $choiceList[$mailbox->getOrigin()->getId()] = str_replace('@', '\@', $mailbox->getLabel());
            }
        }

        return $choiceList;
    }

    /**
     * @return Organization|null
     */
    protected function getOrganization()
    {
        return $this->securityFacade->getOrganization();
    }
}
