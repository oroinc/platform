<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Writer\EntityFieldWriter;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

/**
 * Event class for getting FieldConfigModel before write in EntityFieldWriter
 * @see EntityFieldWriter
 */
class AfterWriteFieldConfigEvent extends SymfonyEvent
{
    public const EVENT_NAME = 'oro_entity_config.after_write_field_config';

    private FieldConfigModel $fieldConfigModel;

    public function __construct(FieldConfigModel $fieldConfigModel)
    {
        $this->fieldConfigModel = $fieldConfigModel;
    }

    public function getFieldConfigModel(): FieldConfigModel
    {
        return $this->fieldConfigModel;
    }
}
