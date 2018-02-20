<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

class AfterRemoveFieldEvent extends SymfonyEvent
{
    /** @var FieldConfigModel */
    protected $fieldConfig;

    /**
     * @param FieldConfigModel $fieldConfig
     */
    public function __construct(FieldConfigModel $fieldConfig)
    {
        $this->fieldConfig = $fieldConfig;
    }

    /**
     * @return FieldConfigModel
     */
    public function getFieldConfig()
    {
        return $this->fieldConfig;
    }
}
