<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAwareRepository;
use Oro\Bundle\EmailBundle\Model\CategorizedRecipient;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Model\RecipientEntity;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contains methods handling email recipients.
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class EmailRecipientsHelper
{
    const ORGANIZATION_PROPERTY = 'organization';
    const EMAIL_IDS_SEPARATOR = ';';

    /** @var AclHelper */
    protected $aclHelper;

    /** @var DQLNameFormatter */
    private $dqlNameFormatter;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var EmailOwnerProvider */
    protected $emailOwnerProvider;

    /** @var ManagerRegistry */
    protected $registry;

    /** @var EmailAddressHelper */
    protected $addressHelper;

    public function __construct(
        AclHelper $aclHelper,
        DQLNameFormatter $dqlNameFormatter,
        NameFormatter $nameFormatter,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        EmailOwnerProvider $emailOwnerProvider,
        ManagerRegistry $registry,
        EmailAddressHelper $addressHelper,
        Indexer $search,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->aclHelper = $aclHelper;
        $this->dqlNameFormatter = $dqlNameFormatter;
        $this->nameFormatter = $nameFormatter;
        $this->configManager = $configManager;
        $this->translator = $translator;
        $this->emailOwnerProvider = $emailOwnerProvider;
        $this->registry = $registry;
        $this->addressHelper = $addressHelper;
        $this->search = $search;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param object $object
     * @param ClassMetadata $objectMetadata
     *
     * @return RecipientEntity
     */
    public function createRecipientEntity($object, ClassMetadata $objectMetadata)
    {
        $identifiers = $objectMetadata->getIdentifierValues($object);
        if (count($identifiers) !== 1) {
            return null;
        }

        return new RecipientEntity(
            $objectMetadata->name,
            reset($identifiers),
            $this->createRecipientEntityLabel($this->nameFormatter->format($object), $objectMetadata->name),
            $this->getOrganizationName($object)
        );
    }

    /**
     * @param string $name
     * @param Organization|null $organization
     *
     * @return Recipient
     */
    public function createRecipientFromEmail($name, Organization $organization = null)
    {
        $em = $this->registry->getManager();
        $email = $this->addressHelper->extractPureEmailAddress($name);
        $owner = $this->emailOwnerProvider->findEmailOwner($em, $email);
        if (!$owner || !$this->isObjectAllowedForOrganization($owner, $organization)) {
            return null;
        }

        $metadata = $em->getClassMetadata(ClassUtils::getClass($owner));

        return new Recipient(
            $email,
            $name,
            $this->createRecipientEntity($owner, $metadata)
        );
    }

    /**
     * @param Recipient $recipient
     *
     * @return array
     */
    public function createRecipientData(Recipient $recipient)
    {
        $data = ['key' => $recipient->getId()];
        if ($recipient->getEntity()) {
            $data = array_merge(
                $data,
                [
                    'contextText' => $recipient->getEntity()->getLabel(),
                    'contextValue' => [
                        'entityClass' => $recipient->getEntity()->getClass(),
                        'entityId' => $recipient->getEntity()->getId(),
                    ],
                    'organization' => $recipient->getEntity()->getOrganization(),
                ]
            );
        }

        return [
            'id' => self::prepareFormRecipientIds($recipient->getId()),
            'text' => $recipient->getName(),
            'data' => json_encode($data),
        ];
    }

    /**
     * @param EmailRecipientsProviderArgs $args
     * @param EmailAwareRepository $repository
     * @param string $alias
     * @param string $entityClass
     *
     * @return Recipient[]
     */
    public function getRecipients(
        EmailRecipientsProviderArgs $args,
        EmailAwareRepository $repository,
        $alias,
        $entityClass
    ) {
        QueryBuilderUtil::checkIdentifier($alias);
        $searchRecipients = $this->search->simpleSearch(
            $args->getQuery(),
            0,
            $args->getLimit(),
            $this->search->getEntityAlias($entityClass)
        );

        $recipients = [];
        if (!$searchRecipients->isEmpty()) {
            $fullNameQueryPart = $this->dqlNameFormatter->getFormattedNameDQL($alias, $entityClass);
            $excludedEmailNames = $args->getExcludedEmailNamesForEntity($entityClass);

            $primaryEmailsQb = $repository
                ->getPrimaryEmailsQb($fullNameQueryPart, $excludedEmailNames)
                ->setMaxResults($args->getLimit());

            $primaryEmailsQb->andWhere($primaryEmailsQb->expr()->in(sprintf('%s.id', $alias), ':entity_id_list'));
            $primaryEmailsQb->setParameter(
                'entity_id_list',
                array_map(function (Result\Item $searchRecipient) {
                    return $searchRecipient->getRecordId();
                }, $searchRecipients->getElements())
            );
            $primaryEmailsResult = $this->getRestrictedResult($primaryEmailsQb, $args);
            $recipients = $this->recipientsFromResult($primaryEmailsResult, $entityClass);
        }

        return $recipients;
    }

    /**
     * @param EmailRecipientsProviderArgs $args
     * @param Recipient[] $recipients
     *
     * @return array
     */
    public static function filterRecipients(EmailRecipientsProviderArgs $args, array $recipients)
    {
        return array_filter($recipients, function (Recipient $recipient) use ($args) {
            return !in_array($recipient->getIdentifier(), $args->getExcludedRecipientIdentifiers()) &&
                stripos($recipient->getName(), $args->getQuery()) !== false;
        });
    }

    /**
     * @param EmailRecipientsProviderArgs $args
     * @param object|null $object
     *
     * @return bool
     */
    public function isObjectAllowed(EmailRecipientsProviderArgs $args, $object = null)
    {
        return $this->isObjectAllowedForOrganization($object, $args->getOrganization());
    }

    /**
     * @param object|null $object
     * @param Organization|null $organization
     *
     * @return bool
     */
    public function isObjectAllowedForOrganization($object = null, Organization $organization = null)
    {
        if (!$organization ||
            !$object ||
            !$this->propertyAccessor->isReadable($object, static::ORGANIZATION_PROPERTY)
        ) {
            return true;
        }

        $objectOrganization = $this->propertyAccessor->getValue($object, static::ORGANIZATION_PROPERTY);
        if (!$organization) {
            return true;
        }

        return $objectOrganization === $organization;
    }

    /**
     * @param array $result
     * @param string $entityClass
     *
     * @return array
     */
    public function recipientsFromResult(array $result, $entityClass)
    {
        $emails = [];
        foreach ($result as $row) {
            $recipient = new CategorizedRecipient(
                $row['email'],
                sprintf('%s <%s>', $row['name'], $row['email']),
                new RecipientEntity(
                    $entityClass,
                    $row['entityId'],
                    $this->createRecipientEntityLabel($row['name'], $entityClass),
                    $row['organization']
                )
            );

            $emails[$recipient->getIdentifier()] = $recipient;
        }

        return $emails;
    }

    /**
     * @param array $result
     *
     * @return array
     */
    public function plainRecipientsFromResult(array $result)
    {
        $emails = [];
        foreach ($result as $row) {
            $recipient = new CategorizedRecipient(
                $row['email'],
                sprintf('%s <%s>', $row['name'], $row['email'])
            );

            $emails[$recipient->getIdentifier()] = $recipient;
        }

        return $emails;
    }

    /**
     * Prepares base64 encoded emails to be used as ids in recipients form for select2 component.
     *
     * @param array|string $ids
     *
     * @return string;
     */
    public static function prepareFormRecipientIds($ids)
    {
        if (is_string($ids)) {
            return base64_encode($ids);
        }

        $ids = array_map("base64_encode", $ids);

        return implode(self::EMAIL_IDS_SEPARATOR, $ids);
    }

    /**
     * Extracts base64 encoded selected email values, that are used as ids in recipients form for select2 component.
     *
     * @param array|string $value
     *
     * @return array;
     */
    public static function extractFormRecipientIds($value)
    {
        if (is_array($value)) {
            return $value;
        }
        /*
         * str_getcsv is used to cover the case if emails are pasted directly with ";" separator
         * and it protects from ";" used  in full email address. (example: "Recipient Name; Name2" <myemail@domain.com>)
         */
        $idsEncoded = str_getcsv($value, self::EMAIL_IDS_SEPARATOR);
        $idsDecoded = array_map(function ($idEncoded) {
            return base64_decode($idEncoded, true) ?: $idEncoded;
        }, $idsEncoded);

        return $idsDecoded;
    }

    /**
     * @param array $emails
     * @param object $object
     *
     * @return Recipient[]
     */
    public function createRecipientsFromEmails(array $emails, $object)
    {
        $objectClass = ClassUtils::getClass($object);
        $em = $this->registry->getManagerForClass($objectClass);
        $objectMetadata = $em->getClassMetadata($objectClass);

        $recipientEntity = $this->createRecipientEntity($object, $objectMetadata);

        $recipients = [];
        foreach ($emails as $email => $name) {
            $recipient = new Recipient($email, $name, $recipientEntity);
            $recipients[$recipient->getIdentifier()] = $recipient;
        }

        return $recipients;
    }

    /**
     * @param QueryBuilder $qb
     * @param EmailRecipientsProviderArgs $args
     *
     * @return array
     */
    protected function getRestrictedResult(QueryBuilder $qb, EmailRecipientsProviderArgs $args)
    {
        if ($args->getOrganization()) {
            $qb
                ->andWhere('o.id = :organization')
                ->setParameter('organization', $args->getOrganization());
        }

        return $this->aclHelper->apply($qb)->getResult();
    }

    /**
     * @param string $label
     * @param string $entityClass
     *
     * @return string
     */
    protected function createRecipientEntityLabel($label, $entityClass)
    {
        $label = trim($label);
        if ($classLabel = $this->getClassLabel($entityClass)) {
            $label .= ' (' . $classLabel . ')';
        }

        return $label;
    }

    /**
     * @param string $className
     *
     * @return null|string
     */
    protected function getClassLabel($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }
        $entityConfig = new EntityConfigId('entity', $className);
        $label = (string) $this->configManager->getConfig($entityConfig)->get('label');

        return $this->translator->trans($label);
    }

    protected function getOrganizationName(object $object): ?string
    {
        $organizationName = null;
        if ($this->propertyAccessor->isReadable($object, static::ORGANIZATION_PROPERTY)) {
            $organization = $this->propertyAccessor->getValue($object, static::ORGANIZATION_PROPERTY);
            if ($organization instanceof OrganizationInterface) {
                $organizationName = $organization->getName();
            }
        }

        return $organizationName;
    }
}
