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

    /** @var MailboxNameHelper */
    private $mailboxNameHelper;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     * @param MailboxManager         $mailboxManager
     * @param MailboxNameHelper      $mailboxNameHelper
     */
    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        MailboxManager $mailboxManager,
        MailboxNameHelper $mailboxNameHelper
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->mailboxManager = $mailboxManager;
        $this->mailboxNameHelper = $mailboxNameHelper;
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
        foreach ($systemMailboxes as $mailbox) {
            $origin = $mailbox->getOrigin();
            if (null !== $origin) {
                $choiceList[str_replace('@', '\@', $mailbox->getLabel())] = $origin->getId();
            }
        }
        foreach ($origins as $origin) {
            if (!in_array($origin->getId(), $choiceList, true) && count($origin->getFolders()) > 0) {
                $mailboxName = $this->mailboxNameHelper->getMailboxName(
                    get_class($origin),
                    $origin->getMailboxName(),
                    null
                );
                $choiceList[str_replace('@', '\@', $mailboxName)] = $origin->getId();
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
