<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

/**
 * The provider for action (another name is a capability) related security metadata.
 */
class ActionSecurityMetadataProvider
{
    /** @var AclAnnotationProvider */
    private $annotationProvider;

    /** @var ActionSecurityMetadata[] */
    private $localCache;

    public function __construct(AclAnnotationProvider $annotationProvider)
    {
        $this->annotationProvider = $annotationProvider;
    }

    /**
     * Checks whether an action with the given name is defined.
     *
     * @param  string $actionName The entity class name
     * @return bool
     */
    public function isKnownAction($actionName)
    {
        $annotation = $this->annotationProvider->findAnnotationById($actionName);

        return null !== $annotation && 'action' === $annotation->getType();
    }

    /**
     * Gets metadata for all actions.
     *
     * @return ActionSecurityMetadata[]
     */
    public function getActions()
    {
        $this->ensureMetadataLoaded();

        return $this->localCache;
    }

    /**
     * Makes sure that metadata are loaded
     */
    private function ensureMetadataLoaded()
    {
        if (null === $this->localCache) {
            $this->localCache = $this->loadMetadata();
        }
    }

    /**
     * @return ActionSecurityMetadata[]
     */
    private function loadMetadata()
    {
        $data = [];
        $annotations = $this->annotationProvider->getAnnotations('action');
        foreach ($annotations as $annotation) {
            $description = $annotation->getDescription();
            if ($description) {
                $description = new Label($description);
            }

            $data[] = new ActionSecurityMetadata(
                $annotation->getId(),
                $annotation->getGroup(),
                new Label($annotation->getLabel()),
                $description,
                $annotation->getCategory()
            );
        }

        return $data;
    }
}
