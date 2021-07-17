<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;

/**
 * Clears AttributeManager cache on entity config update events
 */
class AttributeManagerCacheListener
{
    /** @var AttributeManager */
    private $attributeManager;

    public function __construct(AttributeManager $attributeManager)
    {
        $this->attributeManager = $attributeManager;
    }

    public function onCreateEntity()
    {
        $this->attributeManager->clearAttributesCache();
    }

    public function onCreateField()
    {
        $this->attributeManager->clearAttributesCache();
    }

    public function onUpdateEntity()
    {
        $this->attributeManager->clearAttributesCache();
    }

    public function onUpdateField()
    {
        $this->attributeManager->clearAttributesCache();
    }

    public function onRenameField()
    {
        $this->attributeManager->clearAttributesCache();
    }
}
