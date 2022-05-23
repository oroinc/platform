<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiValidation;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class FormValidationTest extends RestJsonApiTestCase
{
    public function testCreateRequests()
    {
        $this->runForEntities(function (string $entityClass, array $excludedActions) {
            if (in_array(ApiAction::CREATE, $excludedActions, true)) {
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
        });
    }

    private function assertOwnershipErrors(
        array $error,
        string $entityClass,
        OwnershipMetadataInterface $ownershipMetadata
    ) {
        if (!isset($error['source']['pointer'])) {
            return;
        }

        $metadata = $this->getApiMetadata($entityClass, ApiAction::CREATE);
        if (null === $metadata) {
            return;
        }

        $this->assertOwnershipError($error, $metadata, $ownershipMetadata->getOwnerFieldName());
        $this->assertOwnershipError($error, $metadata, $ownershipMetadata->getOrganizationFieldName());
    }

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
