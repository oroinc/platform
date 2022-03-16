<?php

namespace Oro\Bundle\EmailBundle\Builder;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;
use Oro\Bundle\EmailBundle\Form\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment as EmailAttachmentModel;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Provider\ParentMessageIdProvider;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

/**
 * Creates EmailUser based on the specified {@see \Oro\Bundle\EmailBundle\Form\Model\Email} model.
 */
class EmailUserFromEmailModelBuilder
{
    private ManagerRegistry $managerRegistry;

    private EmailEntityBuilder $emailEntityBuilder;

    private EmailOriginHelper $emailOriginHelper;

    private ParentMessageIdProvider $parentMessageIdProvider;

    private EmailActivityManager $emailActivityManager;

    public function __construct(
        ManagerRegistry $managerRegistry,
        EmailEntityBuilder $emailEntityBuilder,
        EmailOriginHelper $emailOriginHelper,
        ParentMessageIdProvider $parentMessageIdProvider,
        EmailActivityManager $emailActivityManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->emailEntityBuilder = $emailEntityBuilder;
        $this->emailOriginHelper = $emailOriginHelper;
        $this->parentMessageIdProvider = $parentMessageIdProvider;
        $this->emailActivityManager = $emailActivityManager;
    }

    public function createFromEmailModel(
        EmailModel $emailModel,
        string $messageId = '',
        \DateTimeInterface $sentAt = null
    ): EmailUser {
        $sentAt = $sentAt ?? new \DateTime('now', new \DateTimeZone('UTC'));

        $emailUserEntity = $this->emailEntityBuilder->emailUser(
            $emailModel->getSubject(),
            $emailModel->getFrom(),
            $emailModel->getTo(),
            $sentAt,
            $sentAt, // sentAt is also a receivedAt for outgoing email.
            $sentAt, // sentAt is also an internalDate for outgoing email.
            Email::NORMAL_IMPORTANCE,
            $emailModel->getCc(),
            $emailModel->getBcc(),
            null,
            $emailModel->getOrganization()
        );

        // Outgoing email should be marked as seen by default.
        $emailUserEntity->setSeen(true);

        $emailBodyEntity = $this->emailEntityBuilder
            ->body($emailModel->getBody(), $emailModel->getType() === 'html', true);

        $this->addEmailAttachments($emailBodyEntity, $emailModel->getAttachments());

        $emailEntity = $emailUserEntity->getEmail();
        $emailEntity
            ->setMessageId($messageId)
            ->setEmailBody($emailBodyEntity)
            ->setRefs($this->parentMessageIdProvider->getParentMessageIdToReply($emailModel));

        return $emailUserEntity;
    }

    /**
     * @param EmailBody $emailBodyEntity
     * @param iterable<EmailAttachmentModel> $emailAttachmentsModels
     */
    private function addEmailAttachments(EmailBody $emailBodyEntity, iterable $emailAttachmentsModels): void
    {
        foreach ($emailAttachmentsModels as $emailAttachmentModel) {
            $emailAttachmentEntity = $emailAttachmentModel->getEmailAttachment();
            if (!$emailAttachmentEntity) {
                continue;
            }

            $this->emailEntityBuilder->addEmailAttachmentEntity($emailBodyEntity, $emailAttachmentEntity);
        }
    }

    public function setEmailOrigin(EmailUser $emailUser, EmailOrigin $emailOrigin): void
    {
        $emailUser
            ->setOrigin($emailOrigin)
            ->setOwner($emailOrigin->getOwner())
            ->setOrganization($emailOrigin->getOrganization());

        $folder = $emailOrigin->getFolder(FolderType::SENT);

        if ($emailOrigin instanceof UserEmailOrigin) {
            if ($emailOrigin->getMailbox() !== null) {
                $emailUser->setOwner(null);
                $emailUser->setMailboxOwner($emailOrigin->getMailbox());
            }

            // In case when 'UserEmailOrigin' origin doesn't have folder, get folder from internal origin
            if (!$folder) {
                $folder = $this->emailOriginHelper
                    ->getEmailOrigin(
                        $emailUser->getEmail()->getFromEmailAddress()->getEmail(),
                        null,
                        InternalEmailOrigin::BAP,
                        false
                    )
                    ?->getFolder(FolderType::SENT) ?: null;
            }
        }

        if ($folder) {
            $emailUser->addFolder($folder);
        }
    }

    public function addActivityEntities(EmailUser $emailUser, iterable $activityEntities = []): void
    {
        // Associate the email with the target entity if exist
        foreach ($activityEntities as $targetEntity) {
            $this->emailActivityManager->addAssociation($emailUser->getEmail(), $targetEntity);
        }
    }

    public function persistAndFlush(): void
    {
        /** @var EntityManager $entityManager */
        $entityManager = $this->managerRegistry->getManagerForClass(Email::class);

        // Persist the email and all related entities such as folders, email addresses etc.
        $this->emailEntityBuilder
            ->getBatch()
            ->persist($entityManager);

        $entityManager->flush();

        $this->emailEntityBuilder->clear();
    }
}
