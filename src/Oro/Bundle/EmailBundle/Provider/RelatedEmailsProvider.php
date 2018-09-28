<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EmailBundle\Entity\EmailInterface;
use Oro\Bundle\EmailBundle\Model\EmailAttribute;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides related recipients for given entity.
 */
class RelatedEmailsProvider
{
    /** @var Registry */
    protected $registry;

    /** @var ConfigManager */
    protected $configManager;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    /** @var PropertyAccessor*/
    protected $propertyAccessor;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    /** @var EntityFieldProvider  */
    protected $entityFieldProvider;

    /**
     * @param Registry                      $registry
     * @param ConfigManager                 $configManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param NameFormatter                 $nameFormatter
     * @param EmailAddressHelper            $emailAddressHelper
     * @param EmailRecipientsHelper         $emailRecipientsHelper
     * @param EntityFieldProvider           $entityFieldProvider
     */
    public function __construct(
        Registry $registry,
        ConfigManager $configManager,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        NameFormatter $nameFormatter,
        EmailAddressHelper $emailAddressHelper,
        EmailRecipientsHelper $emailRecipientsHelper,
        EntityFieldProvider $entityFieldProvider
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->nameFormatter = $nameFormatter;
        $this->emailAddressHelper = $emailAddressHelper;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
        $this->entityFieldProvider = $entityFieldProvider;
    }

    /**
     * @param object $object
     * @param int $depth
     * @param bool $ignoreAcl
     * @param Organization|null $organization
     *
     * @return Recipient[]
     */
    public function getRecipients($object, $depth = 1, $ignoreAcl = false, Organization $organization = null)
    {
        $recipients = [];

        if ($this->isAccessDenyForOrganization($object, $ignoreAcl, $organization)) {
            return $recipients;
        }

        if (!$depth || ($ignoreAcl || !$this->authorizationChecker->isGranted('VIEW', $object))) {
            if (!$depth || $this->tokenAccessor->getUser() !== $object) {
                return $recipients;
            }
        }

        $className = ClassUtils::getClass($object);
        $metadata = $this->getMetadata($className);
        $attributes = $this->initAttributes($className, $metadata);

        $relations = $this->entityFieldProvider->getRelations($className);

        foreach ($relations as $relation) {
            if (is_a($relation['related_entity_name'], EmailInterface::class, true)) {
                $attributes[] = new EmailAttribute($relation['name'], true);
            } else {
                if ($depth > 1) {
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
            $this->createRecipientsFromEmails(
                $this->createEmailsFromAttributes($attributes, $object),
                $object,
                $metadata
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
     * @param EmailAttribute[] $attributes
     * @param object $object
     *
     * @return array
     */
    protected function createEmailsFromAttributes(array $attributes, $object)
    {
        $emails = [];

        foreach ($attributes as $attribute) {
            $value = $this->getPropertyAccessor()->getValue($object, $attribute->getName());
            if (!$value instanceof \Traversable) {
                $value = [$value];
            }

            foreach ($value as $email) {
                if (is_scalar($email)) {
                    $emails[$email] = $this->formatEmail($object, $email);
                } elseif ($email instanceof EmailInterface) {
                    $emails[$email->getEmail()] = $this->formatEmail($email->getEmailOwner(), $email->getEmail());
                }
            }
        }

        return $emails;
    }

    /**
     * @param array $emails
     * @param object $object
     * @param ClassMetadata $objectMetadata
     *
     * @return Recipient[]
     */
    protected function createRecipientsFromEmails(array $emails, $object, ClassMetadata $objectMetadata)
    {
        $recipientEntity = $this->emailRecipientsHelper->createRecipientEntity($object, $objectMetadata);

        $recipients = [];
        foreach ($emails as $email => $name) {
            $recipient = new Recipient($email, $name, $recipientEntity);
            $recipients[$recipient->getIdentifier()] = $recipient;
        }

        return $recipients;
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @return EmailAttribute[]
     */
    protected function getFieldAttributes(ClassMetadata $metadata)
    {
        $attributes = [];
        foreach ($metadata->fieldNames as $fieldName) {
            if (!$this->configManager->hasConfig($metadata->name, $fieldName)) {
                continue;
            }

            if ($this->configManager->isHiddenModel($metadata->name, $fieldName)) {
                continue;
            }

            if (false !== stripos($fieldName, 'email')) {
                $extendFieldConfig = $this->configManager->getFieldConfig('extend', $metadata->name, $fieldName);
                if (!$extendFieldConfig->is('is_deleted')) {
                    $attributes[] = new EmailAttribute($fieldName);
                }
                continue;
            }

            $entityFieldConfig = $this->configManager->getFieldConfig('entity', $metadata->name, $fieldName);
            if ($entityFieldConfig->get('contact_information') === 'email') {
                $attributes[] = new EmailAttribute($fieldName);
            }
        }

        return $attributes;
    }

    /**
     * @param object|null $owner
     * @param string $email
     *
     * @return string
     */
    protected function formatEmail($owner, $email)
    {
        if (!$owner) {
            return $email;
        }

        $ownerName = $this->nameFormatter->format($owner);

        return $this->emailAddressHelper->buildFullEmailAddress($email, $ownerName);
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
     * @param string $className
     *
     * @return ClassMetadata
     */
    protected function getMetadata($className)
    {
        $em = $this->registry->getManagerForClass($className);

        return $em->getClassMetadata($className);
    }

    /**
     * @param string $className
     * @param ClassMetadata $metadata
     * @return array
     */
    protected function initAttributes($className, $metadata)
    {
        $attributes = [];
        if (is_a($className, EmailHolderInterface::class, true)) {
            $attributes[] = new EmailAttribute('email');
        }
        $attributes = array_merge($attributes, $this->getFieldAttributes($metadata));

        return $attributes;
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
