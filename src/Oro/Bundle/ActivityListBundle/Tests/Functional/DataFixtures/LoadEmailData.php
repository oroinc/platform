<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

class LoadEmailData extends AbstractFixture implements ContainerAwareInterface
{
    private $emailData = [
        1 => [
            'email' => 'test@test.test',
            'subject' => 'Subject',
            'fromName' => 'from',
            'reference' => 'first_activity',
            'thread' => false
        ],
        2 => [
            'email' => 'test2@test.test',
            'subject' => 'Subject2',
            'fromName' => 'from2',
            'reference' => 'second_activity',
            'thread' => 'first_thread',
            'isHead' => true
        ],
        3 => [
            'email' => 'test3@test.test',
            'subject' => 'Subject3',
            'fromName' => 'from3',
            'reference' => 'third_activity',
            'thread' => 'first_thread',
            'isHead' => false
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $thread = new EmailThread();
        $manager->persist($thread);
        $this->setReference('first_thread', $thread);

        foreach ($this->emailData as $id => $data) {
            $emailAddress = new EmailAddressProxy();
            $emailAddress->setEmail($data['email']);

            $email = new Email();
            $email->setSubject($data['subject']);
            $email->setFromName($data['fromName']);
            $email->setSentAt(new \DateTime());
            $email->setInternalDate(new \DateTime());
            $email->setMessageId($id);

            if ($data['thread']) {
                $email->setThread($this->getReference($data['thread']));
                $email->setHead($data['isHead']);
            }

            $email->setFromEmailAddress($emailAddress);
            $manager->persist($email);
            $this->setReference($data['reference'], $email);
        }

        $manager->flush();
    }
}
