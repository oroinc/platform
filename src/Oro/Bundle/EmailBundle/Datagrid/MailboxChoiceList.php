<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class MailboxChoiceList
{
    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var MailboxManager */
    private $mailboxManager;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     * @param MailboxManager         $mailboxManager
     */
    public function __construct(TokenAccessorInterface $tokenAccessor, MailboxManager $mailboxManager)
    {
        $this->tokenAccessor = $tokenAccessor;
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
            $this->tokenAccessor->getUser(),
            $this->getOrganization()
        );
        $origins = $this->mailboxManager->findAvailableOrigins(
            $this->tokenAccessor->getUser(),
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
        return $this->tokenAccessor->getOrganization();
    }
}
