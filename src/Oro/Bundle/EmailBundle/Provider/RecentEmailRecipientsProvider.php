<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailRecipientRepository;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Model\RecipientEntity;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class RecentEmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var Registry */
    protected $registry;

    /** @var EmailOwnerProvider */
    protected $emailOwnerProvider;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    /**
     * @param SecurityFacade $securityFacade
     * @param RelatedEmailsProvider $relatedEmailsProvider
     * @param AclHelper $aclHelper
     * @param Registry $registry
     * @param EmailOwnerProvider $emailOwnerProvider
     * @param EmailRecipientsHelper $emailRecipientsHelper
     */
    public function __construct(
        SecurityFacade $securityFacade,
        RelatedEmailsProvider $relatedEmailsProvider,
        AclHelper $aclHelper,
        Registry $registry,
        EmailOwnerProvider $emailOwnerProvider,
        EmailRecipientsHelper $emailRecipientsHelper
    ) {
        $this->securityFacade = $securityFacade;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->aclHelper = $aclHelper;
        $this->registry = $registry;
        $this->emailOwnerProvider = $emailOwnerProvider;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        if (null === $user = $this->securityFacade->getLoggedUser()) {
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
            $owner = $this->emailOwnerProvider->findEmailOwner($this->registry->getManager(), $email);
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
     * @param object|null $owner
     *
     * @return RecipientEntity|null
     */
    protected function createRecipientEntity($owner = null)
    {
        if (!$owner) {
            return null;
        }

        $metadata = $this->registry->getManager()->getClassMetadata(ClassUtils::getClass($owner));

        return $this->emailRecipientsHelper->createRecipientEntity($owner, $metadata);
    }

    /**
     * @param array $result
     */
    protected function emailsFromResult(array $result)
    {
        $emails = [];
        foreach ($result as $row) {
            $emails[$row['email']] = $row['name'];
        }

        return $emails;
    }

    /**
     * {@inheritdoc}
     */
    public function getSection()
    {
        return 'oro.email.autocomplete.recently_used';
    }

    /**
     * @return EmailRecipientRepository
     */
    protected function getEmailRecipientRepository()
    {
        return $this->registry->getRepository('OroEmailBundle:EmailRecipient');
    }
}
