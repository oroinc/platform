<?php

namespace Oro\Bundle\EmailBundle\Migrations\DataFixtures\Demo\ORM\v1_0;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\Email;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadEmails extends AbstractFixture implements ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $folder = $manager->getRepository('OroEmailBundle:EmailFolder')->findOneByType(EmailFolder::SENT);

        if (!$folder) {
            throw new \RuntimeException('Fixture with OroEmailBundle:EmailFolder should be loaded first.');
        }

        /** @var EmailAddressManager $emailAddressManager */
        $emailAddressManager = $this->container->get('oro_email.email.address.manager');
        $fromEmail = $emailAddressManager->newEmailAddress();
        $fromEmail->setEmail(uniqid().'@gmail.com');

        $attachmentContent = new EmailAttachmentContent();
        $attachmentContent
            ->setContentTransferEncoding('UTF-8')
            ->setValue('<html><body>Test attachment</body></html>');

        $attachment = new EmailAttachment();
        $attachment
            ->setContentType('text/html')
            ->setContent($attachmentContent)
            ->setFileName('index.html');

        $emailBody = new EmailBody();
        $emailBody->setBodyIsText(true)->setContent('Hello! This is a fixture email. Thanks!');
        $emailBody->addAttachment($attachment);

        $email = new Email();
        $email->setSubject(uniqid())
            ->setFromName(uniqid())
            ->setReceivedAt(new \DateTime())
            ->setSentAt(new \DateTime())
            ->setInternalDate(new \DateTime())
            ->setFolder($folder)
            ->setEmailBody($emailBody)
            ->setFromEmailAddress($fromEmail)
            ->setMessageId(uniqid());

        $manager->persist($fromEmail);
        $manager->persist($email);

        $manager->flush();
    }
}
