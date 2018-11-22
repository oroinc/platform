<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class FormValidationTest extends RestJsonApiTestCase
{
    /**
     * @param string   $entityClass
     * @param string[] $excludedActions
     *
     * @dataProvider getEntities
     */
    public function testCreateRequests($entityClass, $excludedActions)
    {
        if (in_array(ApiActions::CREATE, $excludedActions, true)) {
            return;
        }

        $entityType = $this->getEntityType($entityClass);
        $response = $this->post(
            ['entity' => $entityType],
            ['data' => ['type' => $entityType, 'attributes' => ['notExistingField' => null]]],
            [],
            false
        );
        self::assertApiResponseStatusCodeEquals(
            $response,
            [Response::HTTP_BAD_REQUEST, Response::HTTP_FORBIDDEN],
            $entityType,
            'post'
        );

        // Make sure that an entity can be created without setting Owner or Organization
        // Owner and or Organization will be set from context for configurable entities
        if ($response->getStatusCode() === Response::HTTP_BAD_REQUEST) {
            $content = self::jsonToArray($response->getContent());
            if (isset($content['errors'])) {
                /** @var OwnershipMetadataProviderInterface $ownershipMetadataProvider */
                $ownershipMetadataProvider = self::getContainer()
                    ->get('oro_security.owner.ownership_metadata_provider');
                $ownershipMetadata = $ownershipMetadataProvider->getMetadata($entityClass);
                if ($ownershipMetadata->hasOwner()) {
                    foreach ($content['errors'] as $error) {
                        $this->assertOwnershipErrors($error, $entityClass, $ownershipMetadata);
                    }
                }
            }
        }
    }

    /**
     * @param array                      $error
     * @param string                     $entityClass
     * @param OwnershipMetadataInterface $ownershipMetadata
     */
    private function assertOwnershipErrors(
        array $error,
        string $entityClass,
        OwnershipMetadataInterface $ownershipMetadata
    ) {
        if (!isset($error['source']['pointer'])) {
            return;
        }

        $metadata = $this->getApiMetadata($entityClass, ApiActions::CREATE);
        if (null === $metadata) {
            return;
        }

        $this->assertOwnershipError($error, $metadata, $ownershipMetadata->getOwnerFieldName());
        $this->assertOwnershipError($error, $metadata, $ownershipMetadata->getOrganizationFieldName());
    }

    /**
     * @param array          $error
     * @param EntityMetadata $metadata
     * @param string|null    $fieldName
     */
    private function assertOwnershipError(array $error, EntityMetadata $metadata, ?string $fieldName)
    {
        if (!$fieldName) {
            return;
        }

        $field = $metadata->getPropertyByPropertyPath($fieldName);
        if (null === $field) {
            return;
        }

        self::assertNotEquals(
            '/data/relationships/' . $field->getName() . '/data',
            $error['source']['pointer'],
            sprintf(
                'Entity %s should not have "%s" constraint for "%s"',
                $metadata->getClassName(),
                $error['title'],
                $field->getName()
            )
        );
    }
}
