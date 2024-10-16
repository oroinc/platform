<?php

namespace Oro\Bundle\EntityExtendBundle\Migration;

use Oro\Bundle\MigrationBundle\Migration\MigrationExtensionManager;

/**
 * Adds possibility to inject an extend options manager into migrations.
 */
class ExtendMigrationExtensionManager extends MigrationExtensionManager
{
    private ?ExtendOptionsManager $extendOptionsManager = null;

    /**
     * Sets a extend options manager.
     */
    public function setExtendOptionsManager(ExtendOptionsManager $extendOptionsManager): void
    {
        $this->extendOptionsManager = $extendOptionsManager;
        foreach ($this->extensions as $extension) {
            if ($extension[0] instanceof ExtendOptionsManagerAwareInterface) {
                $extension[0]->setExtendOptionsManager($this->extendOptionsManager);
            }
        }
    }

    #[\Override]
    protected function configureExtension(object $obj): void
    {
        parent::configureExtension($obj);
        if (null !== $this->extendOptionsManager && $obj instanceof ExtendOptionsManagerAwareInterface) {
            $obj->setExtendOptionsManager($this->extendOptionsManager);
        }
    }
}
