<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\MailboxEmail;

class MailboxEmailOwnerProvider implements EmailOwnerProviderInterface
{

    /**
     * {@inheritdoc}
     */
    public function getEmailOwnerClass()
    {
        return 'Oro\Bundle\EmailBundle\Entity\Mailbox';
    }

    /**
     * {@inheritdoc}
     */
    public function findEmailOwner(EntityManager $em, $email)
    {
        /** @var Mailbox $contact */
        $contact = null;

        /** @var MailboxEmail $emailEntity */
        $emailEntity = $em->getRepository('OroEmailBundle:MailboxEmail')
                          ->findOneBy(array('email' => $email));
        if ($emailEntity !== null) {
            $contact = $emailEntity->getOwner();
        }

        return $contact;
    }
}
