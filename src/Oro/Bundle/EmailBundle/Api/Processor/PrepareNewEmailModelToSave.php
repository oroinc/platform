<?php

namespace Oro\Bundle\EmailBundle\Api\Processor;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EmailBundle\Api\Model\Email as EmailModel;
use Oro\Bundle\EmailBundle\Api\Model\EmailAddress;
use Oro\Bundle\EmailBundle\Api\Model\EmailAddress as EmailAddressModel;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Prepares the Email model created by "create" action to save into the database.
 */
class PrepareNewEmailModelToSave implements ProcessorInterface
{
    private const DEFAULT_FOLDER_NAME = 'Other';

    private DoctrineHelper $doctrineHelper;
    private EmailEntityBuilder $emailEntityBuilder;
    private EmailAddressHelper $emailAddressHelper;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EmailEntityBuilder $emailEntityBuilder,
        EmailAddressHelper $emailAddressHelper,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->emailEntityBuilder = $emailEntityBuilder;
        $this->emailAddressHelper = $emailAddressHelper;
        $this->tokenAccessor = $tokenAccessor;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $emailOrigin = $context->get(LoadEmailOrigin::EMAIL_ORIGIN);
        if (null === $emailOrigin) {
            throw new \LogicException('The email origin does not exist.');
        }

        /** @var EmailModel $emailModel */
        $emailModel = $context->getData();

        $emailUser = $this->processEmailUser($emailOrigin, $emailModel, $context->getIncludedEntities());

        $email = $emailUser->getEmail();
        $emailModel->setEntity($email);
        $email->setMessageId($emailModel->getMessageId());
        $email->setMultiMessageId($emailModel->getMessageIds());
        $email->setRefs($this->buildRefs($emailModel->getRefs()));
        $email->setXMessageId($emailModel->getXMessageId());
        $email->setXThreadId($emailModel->getXThreadId());
        $email->setAcceptLanguageHeader($emailModel->getAcceptLanguage());

        $this->processBody($emailModel, $email);

        $persistedEntities = $this->emailEntityBuilder->getBatch()
            ->persist($this->doctrineHelper->getEntityManagerForClass(Email::class), true);

        foreach ($persistedEntities as $entity) {
            if ($entity !== $email) {
                $context->addAdditionalEntity($entity);
            }
        }
    }

    private function processEmailUser(
        EmailOrigin $emailOrigin,
        EmailModel $emailModel,
        ?IncludedEntityCollection $includedEntities
    ): EmailUser {
        $emailUser = null;
        if (null !== $includedEntities) {
            $emailUser = $this->findCurrentEmailUser($includedEntities);
            if (null !== $emailUser) {
                $includedEntities->remove(
                    $includedEntities->getClass($emailUser),
                    $includedEntities->getId($emailUser)
                );
            }
        }

        if (null === $emailUser) {
            $emailUser = $this->createEmailUser($emailOrigin, $emailModel);
        } else {
            $this->initializeEmailUser($emailUser, $emailModel);
        }
        $emailUser->setOrigin($emailOrigin);

        return $emailUser;
    }

    private function createEmailUser(EmailOrigin $emailOrigin, EmailModel $emailModel): EmailUser
    {
        $emailUser = new EmailUser();
        $this->initializeEmailUser($emailUser, $emailModel);
        $emailUser->setReceivedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $owner = $this->tokenAccessor->getUser();
        if ($owner instanceof User) {
            $emailUser->setOwner($owner);
        }
        $organization = $this->tokenAccessor->getOrganization();
        if (null !== $organization) {
            $emailUser->setOrganization($organization);
        } elseif (null !== $owner) {
            $emailUser->setOrganization($owner->getOrganization());
        }

        $folder = $emailOrigin->getFolder(FolderType::OTHER, self::DEFAULT_FOLDER_NAME);
        if (null === $folder) {
            $folder = $this->emailEntityBuilder->folderOther(self::DEFAULT_FOLDER_NAME, self::DEFAULT_FOLDER_NAME);
            $emailOrigin->addFolder($folder);
        }
        $emailUser->addFolder($folder);

        return $emailUser;
    }

    private function initializeEmailUser(EmailUser $emailUser, EmailModel $emailModel): void
    {
        $this->emailEntityBuilder->initializeEmailUser(
            $emailUser,
            $emailModel->getEntity(),
            $emailModel->getSubject(),
            $this->buildFullEmailAddress($emailModel->getFrom()),
            $this->buildFullEmailAddresses($emailModel->getToRecipients()),
            $emailModel->getSentAt(),
            $emailModel->getInternalDate(),
            $this->transformImportance($emailModel->getImportance()),
            $this->buildFullEmailAddresses($emailModel->getCcRecipients()),
            $this->buildFullEmailAddresses($emailModel->getBccRecipients())
        );
    }

    private function processBody(EmailModel $emailModel, Email $email): void
    {
        $bodySynced = false;
        $emailBodyModel = $emailModel->getBody();
        if (null !== $emailBodyModel) {
            $emailBody = $this->emailEntityBuilder->body(
                $emailBodyModel->getContent(),
                $emailBodyModel->getType() === 'html'
            );
            $email->setEmailBody($emailBody);
            $bodySynced = true;
        }
        $email->setBodySynced($bodySynced);
    }

    private function findCurrentEmailUser(IncludedEntityCollection $includedEntities): ?EmailUser
    {
        $organizationId = $this->tokenAccessor->getOrganizationId();
        $userId = $this->tokenAccessor->getUserId();
        foreach ($includedEntities as $entity) {
            $entityClass = $includedEntities->getClass($entity);
            if ($entityClass
                && is_a($entityClass, EmailUser::class, true)
                && $entity->getOrganization()->getId() === $organizationId
                && $entity->getOwner()->getId() === $userId
            ) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * @param Collection<int, EmailAddressModel> $items
     *
     * @return string[]|null
     */
    private function buildFullEmailAddresses(Collection $items): ?array
    {
        if ($items->isEmpty()) {
            return null;
        }

        $result = [];
        foreach ($items as $item) {
            $result[] = $this->buildFullEmailAddress($item);
        }

        return $result;
    }

    private function buildFullEmailAddress(?EmailAddress $emailAddress): string
    {
        if (null === $emailAddress) {
            return '';
        }

        return $this->emailAddressHelper->buildFullEmailAddress($emailAddress->getEmail(), $emailAddress->getName());
    }

    private function buildRefs(?array $refs): ?string
    {
        if (null === $refs) {
            return null;
        }

        return implode(',', $refs);
    }

    private function transformImportance(?string $importance): int
    {
        switch ($importance) {
            case 'high':
                return Email::HIGH_IMPORTANCE;
            case 'low':
                return Email::LOW_IMPORTANCE;
            default:
                return Email::NORMAL_IMPORTANCE;
        }
    }
}
