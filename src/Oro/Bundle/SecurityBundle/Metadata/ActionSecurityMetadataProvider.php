<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

/**
 * The provider for action (another name is a capability) related security metadata.
 */
class ActionSecurityMetadataProvider
{
    /** @var AclAttributeProvider */
    private $attributeProvider;

    /** @var ActionSecurityMetadata[] */
    private $localCache;

    public function __construct(AclAttributeProvider $attributeProvider)
    {
        $this->attributeProvider = $attributeProvider;
    }

    /**
     * Checks whether an action with the given name is defined.
     *
     * @param  string $actionName The entity class name
     * @return bool
     */
    public function isKnownAction($actionName)
    {
        $attribute = $this->attributeProvider->findAttributeById($actionName);

        return null !== $attribute && 'action' === $attribute->getType();
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
        $attributes = $this->attributeProvider->getAttributes('action');
        foreach ($attributes as $attribute) {
            $description = $attribute->getDescription();
            if ($description) {
                $description = new Label($description);
            }

            $data[] = new ActionSecurityMetadata(
                $attribute->getId(),
                $attribute->getGroup(),
                new Label($attribute->getLabel()),
                $description,
                $attribute->getCategory()
            );
        }

        return $data;
    }
}
