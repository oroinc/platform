<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EmailBundle\Entity\Mailbox;

class MailboxChoiceList
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
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
        $results = $repo->findAll();

        $choiceList = [];
        foreach ($results as $mailbox) {
            $choiceList[$mailbox->getId()] = $mailbox->getLabel();
        }

        return $choiceList;
    }
}
