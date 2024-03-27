<?php

namespace Oro\Bundle\SanitizeBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;

/**
 * Collects entities metadata across all connections excepts entity config one.
 */
class EntityAllMetadataProvider
{
    private ?string $configConnectionName = null;
    private ?array $metadataList = null;

    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    public function setConfigConnactionName(string $configConnectionName = null): void
    {
        $this->configConnectionName = $configConnectionName;
    }

    public function getAllMetadata(): array
    {
        if (null === $this->metadataList) {
            $metadataList = [];

            foreach ($this->doctrine->getManagers() as $emName => $em) {
                if ($emName === $this->configConnectionName) {
                    continue;
                }

                array_push($metadataList, ...$em->getMetadataFactory()->getAllMetadata());
            }
            $this->metadataList = $metadataList;
        }

        return $this->metadataList;
    }
}
