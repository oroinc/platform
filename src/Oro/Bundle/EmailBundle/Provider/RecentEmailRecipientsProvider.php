<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Model\RecipientEntity;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provider for email recipient list based on email that was used in last 30 days.
 */
class RecentEmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    private TokenAccessorInterface $tokenAccessor;
    private RelatedEmailsProvider $relatedEmailsProvider;
    private AclHelper $aclHelper;
    private ManagerRegistry $doctrine;
    private EmailOwnerProvider $emailOwnerProvider;
    private EmailRecipientsHelper $emailRecipientsHelper;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        RelatedEmailsProvider $relatedEmailsProvider,
        AclHelper $aclHelper,
        ManagerRegistry $doctrine,
        EmailOwnerProvider $emailOwnerProvider,
        EmailRecipientsHelper $emailRecipientsHelper
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->aclHelper = $aclHelper;
        $this->doctrine = $doctrine;
        $this->emailOwnerProvider = $emailOwnerProvider;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        $user = $this->tokenAccessor->getUser();
        if (null === $user) {
            return [];
        }

        $userEmailAddresses = array_keys($this->relatedEmailsProvider->getEmails($user, 1, true));

        $recipientsQb = $this->getEmailRecipientRepository()
            ->getEmailsUsedInLast30DaysQb(
                $userEmailAddresses,
                [],
                $args->getQuery()
            )
            ->setMaxResults($args->getLimit());

        $emails = $this->emailsFromResult($this->aclHelper->apply($recipientsQb)->getResult());

        $result = [];
        foreach ($emails as $email => $name) {
            $owner = $this->emailOwnerProvider->findEmailOwner($this->doctrine->getManager(), $email);
            if (!$this->emailRecipientsHelper->isObjectAllowed($args, $owner)) {
                continue;
            }

            $result[] = new Recipient(
                $email,
                $name,
                $this->createRecipientEntity($owner)
            );
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getSection(): string
    {
        return 'oro.email.autocomplete.recently_used';
    }

    private function createRecipientEntity(?object $owner = null): ?RecipientEntity
    {
        if (!$owner) {
            return null;
        }

        return $this->emailRecipientsHelper->createRecipientEntity(
            $owner,
            $this->doctrine->getManager()->getClassMetadata(ClassUtils::getClass($owner))
        );
    }

    private function emailsFromResult(array $result): array
    {
        $emails = [];
        foreach ($result as $row) {
            $emails[$row['email']] = $row['name'];
        }

        return $emails;
    }

    private function getEmailRecipientRepository(): EmailRecipientRepository
    {
        return $this->doctrine->getRepository(EmailRecipient::class);
    }
}
