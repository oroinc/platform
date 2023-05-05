<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Entity\EmailInterface;
use Oro\Bundle\EmailBundle\Model\EmailAttribute;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides related recipients for given entity.
 */
class RelatedEmailsProvider
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    /** @var EntityFieldProvider */
    protected $entityFieldProvider;

    /** @var EmailAttributeProvider */
    private $emailAttributeProvider;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        EmailRecipientsHelper $emailRecipientsHelper,
        EntityFieldProvider $entityFieldProvider,
        EmailAttributeProvider $emailAttributeProvider
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
        $this->entityFieldProvider = $entityFieldProvider;
        $this->emailAttributeProvider = $emailAttributeProvider;
    }

    /**
     * @param object $object
     * @param int $depth
     * @param bool $ignoreAcl
     * @param Organization|null $organization
     *
     * @return Recipient[]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getRecipients($object, $depth = 1, $ignoreAcl = false, Organization $organization = null)
    {
        $recipients = [];

        $className = ClassUtils::getClass($object);
        $attributes = $this->emailAttributeProvider->getAttributes($className);
        $relations = $this->entityFieldProvider->getRelations($className);

        if (empty($attributes) && empty($relations)) {
            return $recipients;
        }

        if ($this->isAccessDenyForOrganization($object, $ignoreAcl, $organization)) {
            return $recipients;
        }

        if (!$depth || ($ignoreAcl || !$this->authorizationChecker->isGranted('VIEW', $object))) {
            if (!$depth || $this->tokenAccessor->getUser() !== $object) {
                return $recipients;
            }
        }

        if ($depth > 1) {
            foreach ($relations as $relation) {
                if (is_a($relation['related_entity_name'], EmailInterface::class, true)) {
                    $attributes[] = new EmailAttribute($relation['name'], true);
                } else {
                    $assocObject = $this->getPropertyAccessor()->getValue($object, $relation['name']);
                    if (!$assocObject instanceof \Traversable && !is_array($assocObject)) {
                        if ($assocObject) {
                            $assocObject = [$assocObject];
                        } else {
                            $assocObject = [];
                        }
                    }
                    foreach ($assocObject as $obj) {
                        $recipients = array_merge(
                            $recipients,
                            $this->getRecipients($obj, $depth - 1, false, $organization)
                        );
                    }
                }
            }
        }

        return array_merge(
            $recipients,
            $this->emailRecipientsHelper->createRecipientsFromEmails(
                $this->emailAttributeProvider->createEmailsFromAttributes($attributes, $object),
                $object
            )
        );
    }

    /**
     * @param object $object
     * @param int $depth
     * @param bool $ignoreAcl
     *
     * @return Recipient[]
     */
    public function getEmails($object, $depth = 1, $ignoreAcl = false)
    {
        $recipients = $this->getRecipients($object, $depth, $ignoreAcl);

        $emails = [];
        /** @var Recipient $recipient */
        foreach ($recipients as $recipient) {
            $emails[$recipient->getEmail()] = $recipient->getId();
        }

        return $emails;
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @param $object
     * @param $ignoreAcl
     * @param Organization|null $organization
     * @return bool
     */
    protected function isAccessDenyForOrganization($object, $ignoreAcl, Organization $organization = null)
    {
        return !$ignoreAcl && !$this->emailRecipientsHelper->isObjectAllowedForOrganization($object, $organization);
    }
}
