<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EmailBundle\Entity\Repository\EmailAwareRepository;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Bundle\EmailBundle\Model\RecipientEntity;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class EmailRecipientsHelper
{
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

    /**
     * @param AclHelper $aclHelper
     * @param DQLNameFormatter $dqlNameFormatter
     * @param NameFormatter $nameFormatter
     * @param ConfigManager $configManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        AclHelper $aclHelper,
        DQLNameFormatter $dqlNameFormatter,
        NameFormatter $nameFormatter,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->aclHelper = $aclHelper;
        $this->dqlNameFormatter = $dqlNameFormatter;
        $this->nameFormatter = $nameFormatter;
        $this->configManager = $configManager;
        $this->translator = $translator;
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
            $this->createRecipientEntityLabel($this->nameFormatter->format($object), $objectMetadata->name)
        );
    }

    /**
     * @param EmailRecipientsProviderArgs $args
     * @param EmailAwareRepository $repository
     * @param string $alias
     * @param string $entityClass
     *
     * @return array
     */
    public function getRecipients(
        EmailRecipientsProviderArgs $args,
        EmailAwareRepository $repository,
        $alias,
        $entityClass
    ) {
        $fullNameQueryPart = $this->dqlNameFormatter->getFormattedNameDQL($alias, $entityClass);

        $primaryEmailsQb = $repository
            ->getPrimaryEmailsQb($fullNameQueryPart, $args->getExcludedEmails(), $args->getQuery())
            ->setMaxResults($args->getLimit());

        $primaryEmailsResult = $this->aclHelper->apply($primaryEmailsQb)->getResult();
        $emails = $this->recipientsFromResult($primaryEmailsResult, $entityClass);

        $limit = $args->getLimit() - count($emails);

        if ($limit > 0) {
            $excludedEmails = array_merge($args->getExcludedEmails(), array_keys($emails));
            $secondaryEmailsQb = $repository
                ->getSecondaryEmailsQb($fullNameQueryPart, $excludedEmails, $args->getQuery())
                ->setMaxResults($limit);

            $secondaryEmailsResult = $this->aclHelper->apply($secondaryEmailsQb)->getResult();
            $emails = array_merge($emails, $this->recipientsFromResult($secondaryEmailsResult, $entityClass));
        }

        return $emails;
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
            return !in_array($recipient->getEmail(), $args->getExcludedEmails()) &&
                stripos($recipient->getName(), $args->getQuery()) !== false;
        });
    }

    /**
     * @param string $label
     * @param string $entityClass
     *
     * @return string
     */
    protected function createRecipientEntityLabel($label, $entityClass)
    {
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
     * @param array $result
     * @param string $entityClass
     *
     * @return array
     */
    protected function recipientsFromResult(array $result, $entityClass)
    {
        $emails = [];
        foreach ($result as $row) {
            $emails[$row['email']] = new Recipient(
                $row['email'],
                sprintf('%s <%s>', $row['name'], $row['email']),
                new RecipientEntity(
                    $entityClass,
                    $row['entityId'],
                    $this->createRecipientEntityLabel($row['name'], $entityClass)
                ),
                $row['organization']
            );
        }

        return $emails;
    }
}
