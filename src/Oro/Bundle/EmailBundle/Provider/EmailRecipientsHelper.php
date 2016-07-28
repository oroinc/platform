<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EmailBundle\Entity\Repository\EmailAwareRepository;
use Oro\Bundle\EmailBundle\Model\CategorizedRecipient;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Model\RecipientEntity;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProvider;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Result;

class EmailRecipientsHelper
{
    const ORGANIZATION_PROPERTY = 'organization';
    const EMAIL_IDS_SEPARATOR   = ';';

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

    /** @var PropertyAccessor*/
    protected $propertyAccessor;

    /** @var EmailOwnerProvider */
    protected $emailOwnerProvider;

    /** @var Registry */
    protected $registry;

    /** @var EmailAddressHelper */
    protected $addressHelper;

    /**
     * @param AclHelper $aclHelper
     * @param DQLNameFormatter $dqlNameFormatter
     * @param NameFormatter $nameFormatter
     * @param ConfigManager $configManager
     * @param TranslatorInterface $translator
     * @param EmailOwnerProvider $emailOwnerProvider
     * @param Registry $registry
     * @param EmailAddressHelper $addressHelper
     * @param Indexer $search
     */
    public function __construct(
        AclHelper $aclHelper,
        DQLNameFormatter $dqlNameFormatter,
        NameFormatter $nameFormatter,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        EmailOwnerProvider $emailOwnerProvider,
        Registry $registry,
        EmailAddressHelper $addressHelper,
        Indexer $search
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

        $organizationName = null;
        if ($this->getPropertyAccessor()->isReadable($object, static::ORGANIZATION_PROPERTY)) {
            $organization = $this->getPropertyAccessor()->getValue($object, static::ORGANIZATION_PROPERTY);
            if ($organization) {
                $organizationName = $organization->getName();
            }
        }

        return new RecipientEntity(
            $objectMetadata->name,
            reset($identifiers),
            $this->createRecipientEntityLabel($this->nameFormatter->format($object), $objectMetadata->name),
            $organizationName
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
        if ($recipientEntity = $recipient->getEntity()) {
            $data = array_merge(
                $data,
                [
                    'contextText'  => $recipient->getEntity()->getLabel(),
                    'contextValue' => [
                        'entityClass' => $recipient->getEntity()->getClass(),
                        'entityId'    => $recipient->getEntity()->getId(),
                    ],
                    'organization' => $recipient->getEntity()->getOrganization(),
                ]
            );
        }

        return [
            'id'    => self::prepareFormRecipientIds($recipient->getId()),
            'text'  => $recipient->getName(),
            'data'  => json_encode($data),
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
            !$this->getPropertyAccessor()->isReadable($object, static::ORGANIZATION_PROPERTY)
        ) {
            return true;
        }

        $objectOrganization = $this->getPropertyAccessor()->getValue($object, static::ORGANIZATION_PROPERTY);
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
     * @return null|string
     */
    protected function getClassLabel($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }
        $entityConfig = new EntityConfigId('entity', $className);
        $label        = $this->configManager->getConfig($entityConfig)->get('label');

        return $this->translator->trans($label);
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
     * Prepares base64 encoded emails to be used as ids in recipients form for select2 component.
     *
     * @param  array|string $ids
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
     * @param  array|string $value
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
            return base64_decode($idEncoded, true) ? : $idEncoded;
        }, $idsEncoded);

        return $idsDecoded;
    }
}
