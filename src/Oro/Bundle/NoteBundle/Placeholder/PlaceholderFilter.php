<?php

namespace Oro\Bundle\NoteBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class PlaceholderFilter
{
    /**
     * @var ConfigProvider
     */
    protected $noteConfigProvider;

    /**
     * @param ConfigProvider      $noteConfigProvider
     */
    public function __construct(ConfigProvider $noteConfigProvider)
    {
        $this->noteConfigProvider  = $noteConfigProvider;
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

        $className = ClassUtils::getClass($entity);

        return
            $this->noteConfigProvider->hasConfig($className)
            && $this->noteConfigProvider->getConfig($className)->is('enabled');
    }
}
