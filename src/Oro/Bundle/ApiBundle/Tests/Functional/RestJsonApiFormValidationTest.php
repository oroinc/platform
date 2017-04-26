<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProvider;

class RestJsonApiFormValidationTest extends RestJsonApiTestCase
{

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var  OwnershipMetadataProvider */
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

        $response = $this->request(
            'POST',
            $this->getUrl('oro_rest_api_post', ['entity' => $entityType]),
            ['data' => ['type' => $entityType, 'attributes' => ['notExistingField' => null]]]
        );
        $this->assertApiResponseStatusCodeEquals($response, [400, 403, 405], $entityType, 'post');

        // Make sure Entity can be created without setting Owner or Organization
        // Owner and or Organization will be set from context for configurable entities
        if ($this->configProvider->hasConfig($entityClass)) {
            $content = json_decode($response->getContent(), true);
            if (isset($content['errors'])) {
                /** @var OwnershipMetadataInterface $classMetadata */
                $classMetadata = $this->metadataProvider->getMetadata($entityClass);
                foreach ($content['errors'] as $error) {
                    if (isset($error['source']['pointer'])) {
                        $this->assertNotEquals(
                            "/data/relationships/{$classMetadata->getOwnerFieldName()}/data",
                            $error['source']['pointer'],
                            "Entity {$entityClass} should not have '{$error['title']}' constraint for 'Owner'"
                        );
                        $this->assertNotEquals(
                            "/data/relationships/{$classMetadata->getGlobalOwnerFieldName()}/data",
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

        $entityType = $this->getEntityType($entityClass);

        $response = $this->request(
            'PATCH',
            $this->getUrl('oro_rest_api_patch', ['entity' => $entityType, 'id' => '1']),
            ['data' => ['type' => $entityType, 'id' => '1', 'attributes' => ['notExistingField' => null]]]
        );
        $this->assertApiResponseStatusCodeEquals($response, [400, 403, 405], $entityType, 'post');
    }
}
