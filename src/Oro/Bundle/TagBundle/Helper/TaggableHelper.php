<?php

namespace Oro\Bundle\TagBundle\Helper;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\TagBundle\Entity\Taggable;

class TaggableHelper
{
    /** @var ConfigProvider */
    protected $tagConfigProvider;

    /** @param ConfigProvider $tagConfigProvider */
    public function __construct(ConfigProvider $tagConfigProvider)
    {
        $this->tagConfigProvider = $tagConfigProvider;
    }

    /**
     * Checks if entity taggable.
     * Entity is taggable if it implements Taggable interface or it configured as taggable.
     *
     * @param string|object $entity
     *
     * @return bool
     */
    public function isTaggable($entity)
    {
        return
            self::isImplementsTaggable($entity) ||
            (
                $entity &&
                $this->tagConfigProvider->hasConfig($entity) &&
                $this->tagConfigProvider->getConfig($entity)->is('enabled')
            );
    }

    /**
     * Checks if tags should be automatically rendered in the entity view
     *
     * @param $entity
     *
     * @return bool
     */
    public function shouldRenderDefault($entity)
    {
        return $this->isTaggable($entity) &&
               $this->tagConfigProvider->hasConfig($entity) &&
               $this->tagConfigProvider->getConfig($entity)->is('enableDefaultRendering');
    }

    /**
     * Checks if column with tags should appear by default on the grid for entity
     *
     * @param string|object $entity
     *
     * @return bool
     */
    public function isEnableGridColumn($entity)
    {
        return
            self::isImplementsTaggable($entity) ||
            (
                $this->tagConfigProvider->hasConfig($entity) &&
                $this->tagConfigProvider->getConfig($entity)->is('enableGridColumn')
            );
    }

    /**
     * Checks if tags filter should appear by default on the grid for entity
     *
     * @param string|object $entity
     *
     * @return bool
     */
    public function isEnableGridFilter($entity)
    {
        return
            self::isImplementsTaggable($entity) ||
            (
                $this->tagConfigProvider->hasConfig($entity) &&
                $this->tagConfigProvider->getConfig($entity)->is('enableGridFilter')
            );
    }

    /**
     * Checks if entity class implements Taggable interface
     *
     * @param object|string $entity
     *
     * @return bool
     */
    public static function isImplementsTaggable($entity)
    {
        return is_a($entity, 'Oro\Bundle\TagBundle\Entity\Taggable', true);
    }

    /**
     * Returns id of entity for tagging relation
     *
     * @param object $entity
     *
     * @return int
     */
    public static function getEntityId($entity)
    {
        return $entity instanceof Taggable ? $entity->getTaggableId() : $entity->getId();
    }
}
