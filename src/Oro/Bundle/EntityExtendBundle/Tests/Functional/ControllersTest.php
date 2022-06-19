<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group dist
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ControllersTest extends AbstractConfigControllerTest
{
    private const RELATION_FIELDS = [
        RelationType::ONE_TO_MANY => [
            'readonly' => true,
            'bidirectional' => true,
            'method' => 'createSelectOneToMany',
        ],
        RelationType::MANY_TO_MANY => [
            'readonly' => false,
            'bidirectional' => false,
            'method' => 'createSelectOneToMany',
        ],
        RelationType::MANY_TO_ONE => [
            'readonly' => false,
            'bidirectional' => false,
            'method' => 'createSelectManyToOne',
        ],
    ];

    /** for Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue  */
    private const NON_EXTENDED_ENTITY = 'Entity Fallback Value';
    /** for Extend\Entity\TestEntity2 */
    private const EXTENDED_ENTITY = 'extend.entity.testentity2.entity_label';

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_entityconfig_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate(): string
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_entityextend_entity_create'));
        $saveButton = $crawler->selectButton('Save');

        $form = $saveButton->form();
        $form['oro_entity_config_type[model][className]'] = 'testExtendedEntity';
        $form['oro_entity_config_type[entity][label]'] = 'test entity label';
        $form['oro_entity_config_type[entity][plural_label]'] = 'test entity plural label';
        $form['oro_entity_config_type[entity][description]'] = 'test entity description';

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form, [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Entity saved', $crawler->html());
        preg_match('/\/view\/(\d+)/', $this->client->getHistory()->current()->getUri(), $matches);
        $this->assertCount(2, $matches);

        return $matches[1];
    }

    /**
     * @depends testCreate
     */
    public function testUpdate(string $id): string
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_entityconfig_update', ['id' => $id])
        );

        $form = $crawler->selectButton('Save')->form();
        $form['oro_entity_config_type[entity][label]'] = 'test entity label updated';
        $form['oro_entity_config_type[entity][plural_label]'] = 'test entity plural label updated';
        $form['oro_entity_config_type[entity][description]'] = 'test entity description updated';
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Entity saved', $crawler->html());

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testView(string $id): string
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_entityconfig_view', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('test entity label updated', $result->getContent());

        return $id;
    }

    /**
     * @depends testView
     */
    public function testCreateFieldSimple(string $id)
    {
        $types = [
            'string', 'integer', 'smallint', 'bigint', 'boolean',
            'decimal', 'date', 'text', 'float', 'money', 'percent'
        ];
        foreach ($types as $type) {
            $crawler = $this->client->request(
                'GET',
                $this->getUrl('oro_entityextend_field_create', ['id' => $id])
            );

            $name = 'name' . strtolower($type);
            $crawler = $this->getCrawlerAfterSubmittingFieldRelationForm($crawler, $name, $type);

            $result = $this->client->getResponse();
            $this->assertHtmlResponseStatusCodeEquals($result, 200);
            $form = $crawler->selectButton('Save and Close')->form();
            $this->client->submit($form);
            $result = $this->client->getResponse();
            $this->assertHtmlResponseStatusCodeEquals($result, 200);
            self::assertStringContainsString('Field saved', $result->getContent());
        }
    }

    /**
     * @depends testView
     */
    public function testCreateEnumField(string $id)
    {
        $types = ['multiEnum', 'enum'];
        $entityName = $this->getEntityConfigModelById((int)$id)->getClassName();

        foreach ($types as $type) {
            $crawler = $this->client->request(
                Request::METHOD_GET,
                $this->getUrl('oro_entityextend_field_create', ['id' => $id])
            );

            $name = 'name' . strtolower($type);
            $translationKeys = $this->generateTranslationKeysByEntityField($entityName, $name);
            $crawler = $this->getCrawlerAfterSubmittingFieldRelationForm($crawler, $name, $type);

            $result = $this->client->getResponse();
            $this->assertHtmlResponseStatusCodeEquals($result, 200);
            $form = $crawler->selectButton('Save and Close')->form();
            $this->client->submit($form);
            $result = $this->client->getResponse();
            $this->assertHtmlResponseStatusCodeEquals($result, 200);
            self::assertStringContainsString('Field saved', $result->getContent());
            $this->assertEntityTranslations($translationKeys);
        }
    }

    /**
     * @depends testView
     */
    public function testCreateFieldRelation(string $id)
    {
        foreach (self::RELATION_FIELDS as $type => $relation) {
            $crawler = $this->client->request(
                'GET',
                $this->getUrl('oro_entityextend_field_create', ['id' => $id])
            );

            $name = 'name' . strtolower($type);
            $crawler = $this->getCrawlerAfterSubmittingFieldRelationForm($crawler, $name, $type);
            $result = $this->client->getResponse();
            $this->assertHtmlResponseStatusCodeEquals($result, 200);

            $saveButton = $crawler->selectButton('Save and Close');
            $fieldUpdateUri = $this->client->getRequest()->getUri();
            $readOnlyValue = $crawler->filter('[name="oro_entity_config_type[extend][relation][bidirectional]"]')
                ->attr('readonly');

            $entities = $crawler->filter('[name="oro_entity_config_type[extend][relation][target_entity]"]')
                ->children();

            $entityLabels = $this->extractEntityLabelsFromDropdown($entities);

            $this->assertContains(self::EXTENDED_ENTITY, $entityLabels);
            if ($type === RelationType::ONE_TO_MANY) {
                $this->assertNotContains(self::NON_EXTENDED_ENTITY, $entityLabels);
            } else {
                $this->assertContains(self::NON_EXTENDED_ENTITY, $entityLabels);
            }

            $form = $saveButton->form();
            $method = $relation['method'];
            $this->$method($form);

            $this->client->followRedirects(true);
            $this->client->submit($form, [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]);
            $result = $this->client->getResponse();

            $this->assertHtmlResponseStatusCodeEquals($result, 200);
            self::assertStringContainsString('Field saved', $result->getContent());

            $isBidirectional = $this->getEntityConfigManager()
                ->getFieldConfig('extend', 'Extend\Entity\testExtendedEntity', $name)
                ->get('bidirectional');

            $this->assertEquals($relation['readonly'], (bool)$readOnlyValue);
            $this->assertEquals($relation['bidirectional'], (bool)$isBidirectional);
            $this->assertBidirectionalIsReadOnlyAfterSave($fieldUpdateUri);
        }
    }

    /**
     * @depends testView
     */
    public function testUpdateSchema(string $id)
    {
        $this->markTestSkipped('Skipped due to Update Schema does not work in test environment');
        $this->client->request(
            'GET',
            $this->getUrl('oro_entityextend_update', ['id' => $id])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    private function assertBidirectionalIsReadOnlyAfterSave(string $fieldUpdateUri): void
    {
        $crawler = $this->client->request('GET', $fieldUpdateUri);
        $readOnlyValue = $crawler->filter('[name="oro_entity_config_type[extend][relation][bidirectional]"]')
            ->attr('readonly');
        $this->assertEquals('readonly', $readOnlyValue);
    }

    private function extractEntityLabelsFromDropdown(Crawler $entities): array
    {
        $entityLabels = [];
        /** @var \DOMElement $entity */
        foreach ($entities as $entity) {
            if ($entity->textContent) {
                $entityLabels[] = $entity->textContent;
            }
        }

        return $entityLabels;
    }

    /**
     * @depends testView
     */
    public function testNonExtendedNonBidirectional(string $id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_entityextend_field_create', ['id' => $id])
        );

        $type = RelationType::MANY_TO_ONE;
        $name = 'namebiok' . strtolower($type);
        $crawler = $this->getCrawlerAfterSubmittingFieldRelationForm($crawler, $name, $type);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $saveButton = $crawler->selectButton('Save and Close');
        $form = $saveButton->form();
        $this->createManyToOneNonExtendableEntitySelect($form);

        $this->client->followRedirects(true);
        $this->client->submit($form, [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $content = $result->getContent();
        self::assertStringContainsString('Field saved', $content);
        self::assertStringContainsString($name, $content);
    }

    /**
     * @depends testView
     */
    public function testNonExtendedBidirectional(string $id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_entityextend_field_create', ['id' => $id])
        );

        $type = RelationType::MANY_TO_ONE;
        $name = 'namebierror' . strtolower($type);
        $crawler = $this->getCrawlerAfterSubmittingFieldRelationForm($crawler, $name, $type);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $saveButton = $crawler->selectButton('Save and Close');
        $form = $saveButton->form();

        $this->createManyToOneNonExtendableEntitySelect($form);
        $this->createBidirectionalSelect($form);
        $form['oro_entity_config_type[extend][relation][bidirectional]'] = '1';

        $this->client->followRedirects(true);
        $this->client->submit($form, [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString(
            'The field can&#039;t be set to &#039;Yes&#039; when target entity isn&#039;t extended.',
            $result->getContent()
        );
    }

    private function assertEntityTranslations(array $translationKeys): void
    {
        /** @var TranslationManager $translationManager */
        $translationManager = $this->getContainer()->get('oro_translation.manager.translation');

        foreach ($translationKeys as $key) {
            $translationKey = $translationManager->findTranslationKey($key);
            $this->assertNotNull($translationKey->getId());
            $this->assertEquals($key, $translationKey->getKey());
            $this->assertEquals(TranslationManager::DEFAULT_DOMAIN, $translationKey->getDomain());
        }
    }

    private function getCrawlerAfterSubmittingFieldRelationForm(Crawler $crawler, string $name, string $type): Crawler
    {
        $continueButton = $crawler->selectButton('Continue');
        $form = $continueButton->form();
        $form['oro_entity_extend_field_type[fieldName]'] = $name;
        $form['oro_entity_extend_field_type[type]'] = $type;
        $this->client->followRedirects(true);

        return $this->client->submit($form, [Router::ACTION_PARAMETER => $continueButton->attr('data-action')]);
    }

    private function getEntityConfigModelById(int $id): EntityConfigModel
    {
        $configManager = $this->getContainer()->get('oro_entity_config.config_manager');

        return $configManager
            ->getEntityManager()
            ->getRepository(EntityConfigModel::class)
            ->find($id);
    }

    private function generateTranslationKeysByEntityField(string $entityName, string $entityField): array
    {
        $enumCode = ExtendHelper::generateEnumCode($entityName, $entityField);

        return [
            ExtendHelper::getEnumTranslationKey('label', $enumCode),
            ExtendHelper::getEnumTranslationKey('plural_label', $enumCode),
            ExtendHelper::getEnumTranslationKey('description', $enumCode),
        ];
    }

    private function getEntityConfigManager(): ConfigManager
    {
        return self::getContainer()->get('oro_entity_config.config_manager');
    }
}
