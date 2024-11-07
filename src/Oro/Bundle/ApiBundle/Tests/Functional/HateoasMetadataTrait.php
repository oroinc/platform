<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessor;
use Oro\Bundle\ApiBundle\Metadata\Extra\ActionMetadataExtra;
use Oro\Bundle\ApiBundle\Metadata\Extra\HateoasMetadataExtra;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Version;

trait HateoasMetadataTrait
{
    private function getMetadata(string $entityClass, string $action): ?array
    {
        $configExtras = [
            new EntityDefinitionConfigExtra($action, ApiAction::GET_LIST === $action)
        ];
        $metadataExtras = [
            new ActionMetadataExtra($action),
            new HateoasMetadataExtra(new FilterValueAccessor())
        ];

        $config = self::getContainer()->get('oro_api.config_provider')->getConfig(
            $entityClass,
            Version::LATEST,
            $this->getRequestType(),
            $configExtras
        );
        $metadata = self::getContainer()->get('oro_api.metadata_provider')->getMetadata(
            $entityClass,
            Version::LATEST,
            $this->getRequestType(),
            $config->getDefinition(),
            $metadataExtras
        );

        return $metadata?->toArray();
    }

    private function assertHateoasLinksForGetListAction(
        array $metadata,
        string $entityClass,
        array $excludedActions
    ): void {
        if (!\in_array(ApiAction::GET, $excludedActions, true)) {
            $this->assertHateoasLinksForPrimaryResource($metadata, $entityClass);
        }
        $this->assertHateoasLinksForAssociations($metadata, $entityClass);
    }

    private function assertHateoasLinksForGetAction(array $metadata, string $entityClass): void
    {
        $this->assertHateoasLinksForPrimaryResource($metadata, $entityClass);
        $this->assertHateoasLinksForAssociations($metadata, $entityClass);
    }

    private function assertHateoasLinksForPrimaryResource(array $metadata, string $entityClass): void
    {
        self::assertArrayHasKey(
            'links',
            $metadata,
            \sprintf('The HATEOAS links are not implemented for "%s" API resource.', $entityClass)
        );
    }

    private function assertHateoasLinksForAssociations(array $metadata, string $entityClass): void
    {
        if (!empty($metadata['associations'])) {
            foreach ($metadata['associations'] as $associationName => $association) {
                $subresource = $this->getSubresourcesProvider()->getSubresource(
                    $entityClass,
                    $associationName,
                    Version::LATEST,
                    $this->getRequestType()
                );
                if (null === $subresource) {
                    continue;
                }
                self::assertArrayHasKey(
                    'relationship_links',
                    $association,
                    \sprintf(
                        'The HATEOAS links are not implemented for "%s" association of "%s" API resource.',
                        $associationName,
                        $entityClass
                    )
                );
            }
        }
    }
}
