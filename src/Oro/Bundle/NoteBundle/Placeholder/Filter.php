<?php

namespace Oro\Bundle\NoteBundle\Placeholder;

use Oro\Bundle\EntityBundle\ORM\EntityClassAccessor;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class Filter
{
    /**
     * @var ConfigProvider
     */
    protected $noteConfigProvider;

    /**
     * @var EntityClassAccessor
     */
    protected $entityClassAccessor;

    /**
     * @param ConfigProvider      $noteConfigProvider
     * @param EntityClassAccessor $entityClassAccessor
     */
    public function __construct(
        ConfigProvider $noteConfigProvider,
        EntityClassAccessor $entityClassAccessor
    ) {
        $this->noteConfigProvider  = $noteConfigProvider;
        $this->entityClassAccessor = $entityClassAccessor;
    }

    /**
     * Checks if the entity can has notes
     *
     * @param object $entity
     * @return bool
     */
    public function isNoteAssociationEnabled($entity)
    {
        if (null === $entity || !is_object($entity)) {
            return false;
        }

        $className = $this->entityClassAccessor->getClass($entity);

        return
            $this->noteConfigProvider->hasConfig($className)
            && $this->noteConfigProvider->getConfig($className)->is('enabled');
    }
}
