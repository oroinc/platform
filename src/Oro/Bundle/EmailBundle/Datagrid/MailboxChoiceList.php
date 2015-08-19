<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
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

        /** @var Mailbox[] $systemMailboxes */
        $systemMailboxes = $repo->findAvailableMailboxes(
            $this->securityFacade->getLoggedUser(),
            $this->securityFacade->getOrganization()
        );
        $origins = $this->getOriginsList();

        $choiceList = [];
        foreach ($origins as $origin) {
            $mailbox = $origin->getMailboxName();
            if (count($origin->getFolders()) > 0) {
                $choiceList[$origin->getId()] = str_replace('@', '\@', $mailbox);
            }
        }
        foreach ($systemMailboxes as $mailbox) {
            if ($mailbox->getOrigin() !== null) {
                $choiceList[$mailbox->getOrigin()->getId()] = $mailbox->getLabel();
            }
        }

        return $choiceList;
    }

    /**
     * @return EmailOrigin[]
     */
    protected function getOriginsList()
    {
        $criteria = [
            'owner' => $this->securityFacade->getLoggedUser(),
            'isActive' => true,
        ];

        return $this->doctrine->getRepository('OroEmailBundle:EmailOrigin')->findBy($criteria);
    }
}
