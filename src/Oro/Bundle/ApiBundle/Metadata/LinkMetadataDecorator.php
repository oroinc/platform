<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * The base class for metadata of a link that extends another link.
 */
abstract class LinkMetadataDecorator implements LinkMetadataInterface
{
    private LinkMetadataInterface $link;

    public function __construct(LinkMetadataInterface $link)
    {
        $this->link = $link;
    }

    #[\Override]
    public function getHref(DataAccessorInterface $dataAccessor): ?string
    {
        return $this->link->getHref($dataAccessor);
    }

    #[\Override]
    public function toArray(): array
    {
        $result = $this->link->toArray();
        $result['decorator'] = \get_class($this);

        return $result;
    }

    #[\Override]
    public function getMetaProperties(): array
    {
        return $this->link->getMetaProperties();
    }

    #[\Override]
    public function hasMetaProperty(string $metaPropertyName): bool
    {
        return $this->link->hasMetaProperty($metaPropertyName);
    }

    #[\Override]
    public function getMetaProperty(string $metaPropertyName): ?MetaAttributeMetadata
    {
        return $this->link->getMetaProperty($metaPropertyName);
    }

    #[\Override]
    public function addMetaProperty(MetaAttributeMetadata $metaProperty): MetaAttributeMetadata
    {
        return $this->link->addMetaProperty($metaProperty);
    }

    #[\Override]
    public function removeMetaProperty(string $metaPropertyName): void
    {
        $this->link->removeMetaProperty($metaPropertyName);
    }
}
