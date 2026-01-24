<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provider that discovers email contact information fields from entity configuration.
 *
 * This provider inspects the entity configuration to find all fields marked as email
 * contact information. It returns a mapping of field labels to field names, allowing
 * the notification system to identify which entity fields contain email addresses that
 * can be used as notification recipients. Only active fields with email contact
 * information type are included in the results.
 */
class ContactInformationEmailsProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    /**
     * @param string $entityName
     *
     * @return array
     */
    public function getRecipients($entityName)
    {
        $emailFields = [];
        $fieldsConfig = $this->configManager->getConfigs('entity', $entityName);

        foreach ($fieldsConfig as $fieldConfig) {
            /** @var FieldConfigId $fieldId */
            $fieldId = $fieldConfig->getId();

            if ($this->configManager->hasConfig($entityName, $fieldId->getFieldName())) {
                $extendFieldConfig = $this->configManager->
                    getFieldConfig('extend', $entityName, $fieldId->getFieldName());

                if ('Active' === $extendFieldConfig->get('state')
                    && 'email' === $fieldConfig->get('contact_information')) {
                    $emailFields[$fieldConfig->get('label')] = $fieldId->getFieldName();
                }
            }
        }

        return $emailFields;
    }
}
