<?php

namespace Oro\Bundle\EmailBundle\DataFixtures\ORM\Email;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroEmail\Cache\OroEmailBundle\Entity\EmailAddressProxy;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\Email;

class LoadEmails extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $folder = $manager->getRepository('OroEmailBundle:EmailFolder')->findOneByType(EmailFolder::SENT);

        if (!$folder) {
            throw new \RuntimeException('Fixture with OroEmailBundle:EmailFolder should be loaded first.');
        }

        $fromEmail = new EmailAddressProxy();
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

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 120;
    }
}
