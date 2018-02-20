<?php

namespace Oro\Bundle\CommentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadEmailData extends AbstractCommentFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
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
