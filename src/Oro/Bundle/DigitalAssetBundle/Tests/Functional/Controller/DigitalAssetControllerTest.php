<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DigitalAssetControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadOrganization::class]);
    }

    public function testChooseUploadWhenInvalidEntityClass(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_digital_asset_widget_choose',
                [
                    'parentEntityClass' => 'InvalidClass',
                    'parentEntityFieldName' => 'sampleField',
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testChooseUploadWhenNotConfigurableField(): void
    {
        $entityClassNameHelper = $this->getContainer()->get('oro_entity.entity_class_name_helper');
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_digital_asset_widget_choose',
                [
                    'parentEntityClass' => $entityClassNameHelper->getUrlSafeClassName(User::class),
                    'parentEntityFieldName' => 'sampleField',
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        // Disables DAM for user avatar field.
        self::toggleDam(false);
    }

    /**
     * Enables/disables DAM for avatar field on user form.
     */
    private static function toggleDam(bool $state): void
    {
        $entityConfigManager = self::getContainer()->get('oro_entity_config.config_manager');
        $attachmentFieldConfig = $entityConfigManager->getFieldConfig('attachment', User::class, 'avatar');
        $attachmentFieldConfig->set('use_dam', $state);
        $entityConfigManager->persist($attachmentFieldConfig);
        $entityConfigManager->flush();
    }
}
