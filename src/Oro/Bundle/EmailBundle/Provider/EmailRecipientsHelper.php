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

        $label = $this->nameFormatter->format($object);
        if ($classLabel = $this->getClassLabel($objectMetadata->name)) {
            $label .= ' (' . $classLabel . ')';
        }

        return new RecipientEntity(
            $objectMetadata->name,
            reset($identifiers),
            $label
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
        $emails = $this->emailsFromResult($primaryEmailsResult);

        $limit = $args->getLimit() - count($emails);

        if ($limit > 0) {
            $excludedEmails = array_merge($args->getExcludedEmails(), array_keys($emails));
            $secondaryEmailsQb = $repository
                ->getSecondaryEmailsQb($fullNameQueryPart, $excludedEmails, $args->getQuery())
                ->setMaxResults($limit);

            $secondaryEmailsResult = $this->aclHelper->apply($secondaryEmailsQb)->getResult();
            $emails = array_merge($emails, $this->emailsFromResult($secondaryEmailsResult));
        }

        return $emails;
    }

    /**
     * @param EmailRecipientsProviderArgs $args
     * @param array $recipients
     *
     * @return array
     */
    public static function filterRecipients(EmailRecipientsProviderArgs $args, array $recipients)
    {
        $keys = array_keys($recipients);
        if ($keys && is_scalar($recipients[$keys[0]])) {
            return static::filterEmails($args, $recipients);
        }

        return array_filter($recipients, function (Recipient $recipient) use ($args) {
            return !in_array($recipient->getEmail(), $args->getExcludedEmails()) &&
                stripos($recipient->getName(), $args->getQuery()) !== false;
        });
    }

    /**
     * @deprecated
     * @param EmailRecipientsProviderArgs $args
     * @param string[] $recipients
     *
     * @return array
     */
    protected static function filterEmails(EmailRecipientsProviderArgs $args, array $recipients)
    {
        $unExcludedEmails = array_filter(array_keys($recipients), function ($email) use ($args) {
            return !in_array($email, $args->getExcludedEmails());
        });

        $unExcludedRecipients = array_intersect_key($recipients, array_flip($unExcludedEmails));

        return array_filter($unExcludedRecipients, function ($email) use ($args) {
            return stripos($email, $args->getQuery()) !== false;
        });
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
     *
     * @return array
     */
    protected function emailsFromResult(array $result)
    {
        $emails = [];
        foreach ($result as $row) {
            $emails[$row['email']] = sprintf('%s <%s>', $row['name'], $row['email']);
        }

        return $emails;
    }
}
