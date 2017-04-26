<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional;

use Oro\Bundle\UIBundle\Route\Router;

/**
 * @group dist
 */
class ControllersTest extends AbstractConfigControllerTest
{
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
            $continueButton = $crawler->selectButton('Continue');
            $form = $continueButton->form();
            $form["oro_entity_extend_field_type[fieldName]"] = "name" . strtolower($type);
            $form["oro_entity_extend_field_type[type]"] = $type;
            $this->client->followRedirects(true);
            $crawler = $this->client
                ->submit(
                    $form,
                    [Router::ACTION_PARAMETER => $continueButton->attr('data-action')]
                );
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
        $types = [
            'oneToMany' => 'createSelectOneToMany',
            'manyToOne' => 'createSelectManyToOne',
            'manyToMany' => 'createSelectOneToMany'
        ];
        foreach ($types as $type => $method) {
            $crawler = $this->client->request(
                'GET',
                $this->getUrl("oro_entityextend_field_create", array('id' => $id))
            );
            $continueButton = $crawler->selectButton('Continue');
            $form = $continueButton->form();
            $form["oro_entity_extend_field_type[fieldName]"] = "name" . strtolower($type);
            $form["oro_entity_extend_field_type[type]"] = $type;
            $this->client->followRedirects(true);
            $crawler = $this->client->submit($form, [Router::ACTION_PARAMETER => $continueButton->attr('data-action')]);
            $result = $this->client->getResponse();
            $this->assertHtmlResponseStatusCodeEquals($result, 200);

            $saveButton = $crawler->selectButton('Save and Close');
            $form = $saveButton->form();

            $this->$method($form);

            $this->client->followRedirects(true);
            $this->client->submit($form, [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]);
            $result = $this->client->getResponse();
            $this->assertHtmlResponseStatusCodeEquals($result, 200);
            $this->assertContains('Field saved', $result->getContent());
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
}
