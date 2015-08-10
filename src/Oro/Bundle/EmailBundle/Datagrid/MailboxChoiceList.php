<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class MailboxChoiceList
{
    /** @var Registry */
    private $doctrine;

    /** @var SecurityFacade */
    private $securityFacade;

    /**
     * @param Registry       $doctrine
     * @param SecurityFacade $securityFacade
     */
    public function __construct(Registry $doctrine, SecurityFacade $securityFacade)
    {
        $this->doctrine = $doctrine;
        $this->securityFacade = $securityFacade;
    }

    /**
     * Returns array of mailbox choices.
     *
     * @return array
     */
    public function getChoiceList()
    {
        $repo = $this->doctrine->getRepository('OroEmailBundle:Mailbox');

        /** @var Mailbox[] $results */
        $results = $repo->findAvailableMailboxes($this->securityFacade->getLoggedUser());

        $choiceList = [];
        foreach ($results as $mailbox) {
            $choiceList[$mailbox->getId()] = $mailbox->getLabel();
        }

        return $choiceList;
    }
}
