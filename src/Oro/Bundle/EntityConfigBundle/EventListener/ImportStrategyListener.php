<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * This listener prevents to change a simple field config to be an attribute, during import.
 */
class ImportStrategyListener
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var ImportStrategyHelper */
    private $strategyHelper;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        TranslatorInterface $translator,
        ImportStrategyHelper $strategyHelper,
        ConfigManager $configManager
    ) {
        $this->translator = $translator;
        $this->strategyHelper = $strategyHelper;
        $this->configManager = $configManager;
    }

    public function onProcessAfter(StrategyEvent $event)
    {
        $context = $event->getContext();
        $entity = $event->getEntity();
        if (!$entity instanceof FieldConfigModel) {
            return;
        }

        $existingEntity = $context->getValue('existingEntity');
        if (!$existingEntity) {
            return;
        }

        $attributeConfig = $this->configManager->createFieldConfigByModel($entity, 'attribute');
        if (!$attributeConfig->is('is_attribute')) {
            return;
        }

        $existingAttributeConfig = $this->configManager->createFieldConfigByModel($existingEntity, 'attribute');
        if ($existingAttributeConfig->is('is_attribute')) {
            return;
        }

        $error = $this->translator->trans('oro.entity_config.import.message.cant_replace_extend_field');
        $context->incrementErrorEntriesCount();
        $this->strategyHelper->addValidationErrors([$error], $context);
        $event->setEntity(null);
    }
}
