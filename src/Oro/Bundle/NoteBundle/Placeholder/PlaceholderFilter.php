<?php

namespace Oro\Bundle\NoteBundle\Placeholder;

use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Entity\Note;

class PlaceholderFilter
{
    /**
     * @var ConfigProvider
     */
    protected $noteConfigProvider;

    /**
     * @param ConfigProvider $noteConfigProvider
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(ConfigProvider $noteConfigProvider, ConfigProvider $entityConfigProvider)
    {
        $this->noteConfigProvider   = $noteConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
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
            && $this->noteConfigProvider->getConfig($className)->is('enabled')
            && $this->entityConfigProvider->hasConfig(
                Note::ENTITY_NAME,
                ExtendHelper::buildAssociationName($className)
            );
    }
}
