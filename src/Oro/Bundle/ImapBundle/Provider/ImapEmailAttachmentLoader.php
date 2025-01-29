<?php

namespace Oro\Bundle\ImapBundle\Provider;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailAttachmentContent;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Exception\EmailAttachmentNotFoundException;
use Oro\Bundle\EmailBundle\Exception\EmailBodyNotFoundException;
use Oro\Bundle\EmailBundle\Provider\EmailAttachmentLoaderInterface;
use Oro\Bundle\ImapBundle\Entity\ImapEmail;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Manager\DTO\Email as EmailDTO;
use Oro\Bundle\ImapBundle\Manager\ImapEmailManagerFactory;

/**
 * This class provides ability to load email attachment
 */
class ImapEmailAttachmentLoader implements EmailAttachmentLoaderInterface
{
    public function __construct(
        private ImapEmailManagerFactory $emailManagerFactory,
        private EntityManager $entityManager
    ) {
    }

    #[\Override]
    public function supports(EmailOrigin $origin)
    {
        return $origin instanceof UserEmailOrigin;
    }

    #[\Override]
    public function loadEmailAttachments(EmailBody $emailBody): array
    {
        $loadedEmail = $this->imapLoadEmailData($emailBody);

        $attachments = [];
        foreach ($loadedEmail->getAttachments() as $attachment) {
            $emailAttachment = (new EmailAttachment())
                ->setFileName($attachment->getFilename());

            $emailContent = (new EmailAttachmentContent())
                ->setContent($attachment->getContent())
                ->setContentTransferEncoding($attachment->getContentTransferEncoding());

            $emailAttachment->setContent($emailContent);
            $attachments[] = $emailAttachment;
        }

        return $attachments;
    }

    public function loadEmailAttachment(EmailBody $emailBody, string $attachmentName): ?EmailAttachment
    {
        $emailAttachment = null;
        $loadedEmail = $this->imapLoadEmailData($emailBody);
        foreach ($loadedEmail->getAttachments() as $attachment) {
            if ($attachment->getFileName() === $attachmentName) {
                $emailAttachment = (new EmailAttachment())
                    ->setContentType($attachment->getContentType())
                    ->setFileName($attachment->getFilename());

                $emailContent = (new EmailAttachmentContent())
                    ->setContent($attachment->getContent())
                    ->setContentTransferEncoding($attachment->getContentTransferEncoding());

                $emailAttachment->setContent($emailContent);
                break;
            }
        }

        return $emailAttachment;
    }

    private function imapLoadEmailData(EmailBody $emailBody): EmailDTO
    {
        $imapEmailRepository = $this->entityManager->getRepository(ImapEmail::class);
        $imapEmail = $imapEmailRepository->findOneBy(['email' => $emailBody->getEmail()]);
        $folder = $imapEmail->getImapFolder()->getFolder();

        /** @var UserEmailOrigin $origin */
        $origin = $folder->getOrigin();
        $manager = $this->emailManagerFactory->getImapEmailManager($origin);
        $manager->selectFolder($folder->getFullName());

        $loadedEmail = $manager->findEmail($imapEmail->getUid());
        if (null === $loadedEmail) {
            throw new EmailBodyNotFoundException($emailBody->getEmail());
        }

        if (empty($loadedEmail->getAttachments())) {
            throw new EmailAttachmentNotFoundException($emailBody->getEmail());
        }

        return $loadedEmail;
    }
}
