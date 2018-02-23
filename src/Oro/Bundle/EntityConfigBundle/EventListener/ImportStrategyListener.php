<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Symfony\Component\Translation\TranslatorInterface;

class ImportStrategyListener
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var ImportStrategyHelper */
    private $strategyHelper;

    /**
     * @param TranslatorInterface $translator
     * @param ImportStrategyHelper $strategyHelper
     */
    public function __construct(TranslatorInterface $translator, ImportStrategyHelper $strategyHelper)
    {
        $this->translator = $translator;
        $this->strategyHelper = $strategyHelper;
    }

    /**
     * @param StrategyEvent $event
     */
    public function onProcessAfter(StrategyEvent $event)
    {
        $context = $event->getContext();
        $entity = $event->getEntity();

        if ($entity instanceof FieldConfigModel && $context->hasOption('check_attributes')) {
            $existingEntity = $context->getValue('existingEntity');

            if ($existingEntity) {
                $attributeData = $existingEntity->toArray('attribute');

                if (empty($attributeData['is_attribute'])) {
                    $error = $this->translator->trans('oro.entity_config.import.message.cant_replace_extend_field');
                    $context->incrementErrorEntriesCount();
                    $this->strategyHelper->addValidationErrors([$error], $context);

                    $event->setEntity(null);
                }
            }
        }
    }
}
