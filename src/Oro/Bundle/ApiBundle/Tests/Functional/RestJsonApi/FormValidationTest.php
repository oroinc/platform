<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\SkippedEntitiesProvider;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;

class FormValidationTest extends RestJsonApiTestCase
{

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var OwnershipMetadataProviderInterface */
    protected $metadataProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->configProvider = $this->getContainer()->get('oro_entity_config.provider.ownership');
        $this->metadataProvider = $this->getContainer()->get('oro_security.owner.ownership_metadata_provider');
    }

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
        self::assertApiResponseStatusCodeEquals($response, [400, 403, 405], $entityType, 'post');

        // Make sure Entity can be created without setting Owner or Organization
        // Owner and or Organization will be set from context for configurable entities
        if ($this->configProvider->hasConfig($entityClass)) {
            $content = json_decode($response->getContent(), true);
            if (isset($content['errors'])) {
                /** @var OwnershipMetadataInterface $classMetadata */
                $classMetadata = $this->metadataProvider->getMetadata($entityClass);
                foreach ($content['errors'] as $error) {
                    if (isset($error['source']['pointer'])) {
                        self::assertNotEquals(
                            '/data/relationships/' . $classMetadata->getOwnerFieldName() . '/data',
                            $error['source']['pointer'],
                            "Entity {$entityClass} should not have '{$error['title']}' constraint for 'Owner'"
                        );
                        self::assertNotEquals(
                            '/data/relationships/' . $classMetadata->getOrganizationFieldName() . '/data',
                            $error['source']['pointer'],
                            "Entity {$entityClass} should not have '{$error['title']}' constraint for 'Organization'"
                        );
                    }
                }
            }
        }
    }

    /**
     * @param string   $entityClass
     * @param string[] $excludedActions
     *
     * @dataProvider getEntities
     */
    public function testUpdateRequests($entityClass, $excludedActions)
    {
        if (in_array(ApiActions::UPDATE, $excludedActions, true)) {
            return;
        }

        if (in_array($entityClass, SkippedEntitiesProvider::getForUpdateAction(), true)) {
            return;
        }

        $entityType = $this->getEntityType($entityClass);

        $response = $this->patch(
            ['entity' => $entityType, 'id' => '1'],
            ['data' => ['type' => $entityType, 'id' => '1', 'attributes' => ['notExistingField' => null]]],
            [],
            false
        );
        self::assertApiResponseStatusCodeEquals($response, [400, 403, 404, 405], $entityType, 'post');
    }
}
