<?php

namespace Oro\Bundle\SanitizeBundle\Tests\Functional\Environment\Provider;

use Oro\Bundle\SanitizeBundle\Provider\EntityAllMetadataProvider;

class EntityAllMetadataProviderDecorator extends EntityAllMetadataProvider
{
    private array $entitiesToFilter = [];
    private ?array $metadataList = null;

    public function setEntitiesToFilter(array $entitiesToFilter = []): void
    {
        $this->metadataList = null;
        $this->entitiesToFilter = $entitiesToFilter;
    }

    #[\Override]
    public function getAllMetadata(): array
    {
        if (null === $this->metadataList) {
            $metadataList = parent::getAllMetadata();
            if (!empty($this->entitiesToFilter)) {
                $metadataList = array_filter($metadataList, function ($metadata) {
                    return in_array($metadata->getName(), $this->entitiesToFilter, true);
                });
            }
            $this->metadataList = $metadataList;
        }

        return $this->metadataList;
    }
}
