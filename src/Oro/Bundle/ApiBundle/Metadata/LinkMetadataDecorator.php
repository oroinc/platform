<?php

namespace Oro\Bundle\ApiBundle\Metadata;

/**
 * The base class for metadata of a link that extends another link.
 */
abstract class LinkMetadataDecorator implements LinkMetadataInterface
{
    /** @var LinkMetadataInterface */
    private $link;

    /**
     * @param LinkMetadataInterface $link The link metadata to be decorated
     */
    public function __construct(LinkMetadataInterface $link)
    {
        $this->link = $link;
    }

    /**
     * {@inheritdoc}
     */
    public function getHref(DataAccessorInterface $dataAccessor): ?string
    {
        return $this->link->getHref($dataAccessor);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = $this->link->toArray();
        $result['decorator'] = get_class($this);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaProperties(): array
    {
        return $this->link->getMetaProperties();
    }

    /**
     * {@inheritdoc}
     */
    public function hasMetaProperty(string $metaPropertyName): bool
    {
        return $this->link->hasMetaProperty($metaPropertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetaProperty(string $metaPropertyName): ?MetaAttributeMetadata
    {
        return $this->link->getMetaProperty($metaPropertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function addMetaProperty(MetaAttributeMetadata $metaProperty): MetaAttributeMetadata
    {
        return $this->link->addMetaProperty($metaProperty);
    }

    /**
     * {@inheritdoc}
     */
    public function removeMetaProperty(string $metaPropertyName): void
    {
        $this->link->removeMetaProperty($metaPropertyName);
    }
}
