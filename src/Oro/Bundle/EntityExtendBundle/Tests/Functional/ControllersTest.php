<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional;

use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @group dist
 */
class ControllersTest extends AbstractConfigControllerTest
{
    const RELATION_FIELDS = [
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

    const NON_EXTENDED_ENTITY = 'Entity fallback value'; // 'Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue';
    const EXTENDED_ENTITY = 'extend.entity.testentity2.entity_label'; // 'Extend\Entity\TestEntity2';

    public function testIndex()
    {
        $this->client->request('GET', $this->getUrl('oro_entityconfig_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testCreate()
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
        $this->assertContains("Entity saved", $crawler->html());
        preg_match('/\/view\/(\d+)/', $this->client->getHistory()->current()->getUri(), $matches);
        $this->assertCount(2, $matches);
        return $matches[1];
    }

    /**
     * @depends testCreate
     * @param int $id
     * @return int
     */
    public function testUpdate($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_entityconfig_update', array('id' => $id))
        );

        $form = $crawler->selectButton('Save')->form();
        $form['oro_entity_config_type[entity][label]'] = 'test entity label updated';
        $form['oro_entity_config_type[entity][plural_label]'] = 'test entity plural label updated';
        $form['oro_entity_config_type[entity][description]'] = 'test entity description updated';
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains("Entity saved", $crawler->html());

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testView($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_entityconfig_view', array('id' => $id))
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('test entity label updated', $result->getContent());

        return $id;
    }

    /**
     * @depends testView
     */
    public function testCreateFieldSimple($id)
    {
        $types = [
            'string', 'integer', 'smallint', 'bigint', 'boolean',
            'decimal', 'date', 'text', 'float', 'money', 'percent'
        ];
        foreach ($types as $type) {
            $crawler = $this->client->request(
                'GET',
                $this->getUrl("oro_entityextend_field_create", array('id' => $id))
            );

            $name = "name" . strtolower($type);
            $crawler = $this->getCrawlerAfterSubmittingFieldRelationForm($crawler, $name, $type);

            $result = $this->client->getResponse();
            $this->assertHtmlResponseStatusCodeEquals($result, 200);
            $form = $crawler->selectButton('Save and Close')->form();
            $this->client->submit($form);
            $result = $this->client->getResponse();
            $this->assertHtmlResponseStatusCodeEquals($result, 200);
            $this->assertContains('Field saved', $result->getContent());
        }
    }

    /**
     * @depends testView
     */
    public function testCreateFieldRelation($id)
    {
        $configManager = $this->getContainer()->get('oro_entity_config.config_manager');

        foreach (static::RELATION_FIELDS as $type => $relation) {
            $crawler = $this->client->request(
                'GET',
                $this->getUrl("oro_entityextend_field_create", array('id' => $id))
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

            $this->assertContains(static::EXTENDED_ENTITY, $entityLabels);
            if ($type === RelationType::ONE_TO_MANY) {
                $this->assertNotContains(static::NON_EXTENDED_ENTITY, $entityLabels);
            } else {
                $this->assertContains(static::NON_EXTENDED_ENTITY, $entityLabels);
            }

            $form = $saveButton->form();
            $method = $relation['method'];
            $this->$method($form);

            $this->client->followRedirects(true);
            $this->client->submit($form, [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]);
            $result = $this->client->getResponse();

            $this->assertHtmlResponseStatusCodeEquals($result, 200);
            $this->assertContains('Field saved', $result->getContent());

            $isBidirectional = $configManager->getFieldConfig('extend', 'Extend\Entity\testExtendedEntity', $name)
                ->get('bidirectional');

            $this->assertEquals($relation['readonly'], (bool)$readOnlyValue);
            $this->assertEquals($relation['bidirectional'], (bool)$isBidirectional);
            $this->assertBidirectionalIsReadOnlyAfterSave($fieldUpdateUri);
        }
    }

    /**
     * @depends testView
     */
    public function testUpdateSchema($id)
    {
        $this->markTestSkipped('Skipped due to Update Schema does not work in test environment');
        $this->client->request(
            'GET',
            $this->getUrl("oro_entityextend_update", array('id' => $id))
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @param string $fieldUpdateUri
     */
    private function assertBidirectionalIsReadOnlyAfterSave($fieldUpdateUri)
    {
        $crawler = $this->client->request('GET', $fieldUpdateUri);
        $readOnlyValue = $crawler->filter('[name="oro_entity_config_type[extend][relation][bidirectional]"]')
            ->attr('readonly');
        $this->assertEquals('readonly', $readOnlyValue);
    }

    /**
     * @param Crawler $entities
     * @return array
     */
    private function extractEntityLabelsFromDropdown(Crawler $entities)
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
     * @param integer $id
     */
    public function testNonExtendedNonBidirectional($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl("oro_entityextend_field_create", array('id' => $id))
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
        $this->assertContains('Field saved', $content);
        $this->assertContains($name, $content);
    }

    /**
     * @depends testView
     * @param integer $id
     */
    public function testNonExtendedBidirectional($id)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl("oro_entityextend_field_create", array('id' => $id))
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
        $form["oro_entity_config_type[extend][relation][bidirectional]"] = "1";

        $this->client->followRedirects(true);
        $this->client->submit($form, [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]);
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains(
            'The field can&#039;t be set to &#039;Yes&#039; when target entity isn&#039;t extended.',
            $result->getContent()
        );
    }

    /**
     * @param Crawler $crawler
     * @param string $name
     * @param string $type
     * @return Crawler
     */
    protected function getCrawlerAfterSubmittingFieldRelationForm($crawler, $name, $type)
    {
        $continueButton = $crawler->selectButton('Continue');
        $form = $continueButton->form();
        $form["oro_entity_extend_field_type[fieldName]"] = $name;
        $form["oro_entity_extend_field_type[type]"] = $type;
        $this->client->followRedirects(true);

        return $this->client->submit($form, [Router::ACTION_PARAMETER => $continueButton->attr('data-action')]);
    }
}
