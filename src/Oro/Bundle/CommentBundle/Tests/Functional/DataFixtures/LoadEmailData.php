<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;

class LoadEmailData extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $emailAddress = new EmailAddressProxy();
        $emailAddress->setEmail('test@test.test');
        $email = new Email();
        $email->setSubject('Subject');
        $email->setFromName('from');
        $email->setSentAt(new \DateTime());
        $email->setInternalDate(new \DateTime());
        $email->setMessageId('1');
        $email->setFromEmailAddress($emailAddress);
        $manager->persist($email);
        $this->setReference('default_activity', $email);
        $manager->flush();
    }
}
