<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;

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
        return $em->getRepository('OroEmailBundle:Mailbox')->findOneByEmail($email);
    }
}
