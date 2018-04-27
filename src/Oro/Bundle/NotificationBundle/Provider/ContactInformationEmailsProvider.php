<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\Translation\TranslatorInterface;

class ContactInformationEmailsProvider
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     */
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
