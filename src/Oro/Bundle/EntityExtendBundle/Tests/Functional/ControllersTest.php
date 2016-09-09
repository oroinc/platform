<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Field\ChoiceFormField;

use Oro\Bundle\UIBundle\Route\Router;
use Oro\Bundle\EntityExtendBundle\Cache\EntityCacheWarmer;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ControllersTest extends WebTestCase
{
    /**
     * @var \Closure
     */
    protected static $warmupCache;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        // These tests breaks isolation between tests by modifying the cache.
        // It leads to an exception in tests run after these ones.
        // Internal Server Error: A model for "Extend\Entity\testExtendedEntity" was not found.
        /** @var EntityCacheWarmer $entityCacheWarmup */
        $entityCacheWarmup = $this->getContainer()->get('oro_entity_extend.entity.cache.warmer');
        $cacheDir = $this->getClient()->getKernel()->getCacheDir();
        self::$warmupCache = function () use ($entityCacheWarmup, $cacheDir) {
            $entityCacheWarmup->warmUp($cacheDir);
        };
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        if (self::$warmupCache) {
            call_user_func(self::$warmupCache);
            self::$warmupCache = null;
        }
    }

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
            $crawler = $this->client->submit($form);
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
            $crawler = $this->client->submit($form, [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]);
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

    protected function createSelectOneToMany($form)
    {
        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select required="required" name="oro_entity_config_type[extend][relation][target_grid][]"' .
            ' id="oro_entity_config_type_extend_relation_target_grid" >' .
            '<option value="" selected="selected"></option> ' .
            '<option value="username">' .
            'Username' .
            '</option> </select> '.
            '<select required="required" name="oro_entity_config_type[extend][relation][target_title][]"' .
            ' id="oro_entity_config_type_extend_relation_target_title" >' .
            '<option value="" selected="selected"></option> ' .
            '<option value="username">' .
            'Username' .
            '</option> </select> '.
            '<select required="required" name="oro_entity_config_type[extend][relation][target_detailed][]"' .
            ' id="oro_entity_config_type_extend_relation_target_detailed" >' .
            '<option value="" selected="selected"></option> ' .
            '<option value="username">' .
            'Username' .
            '</option> </select> '
        );

        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(1));
        $form->set($field);
        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(2));
        $form->set($field);
        $form["oro_entity_config_type[extend][relation][target_entity]"] = 'Oro\Bundle\UserBundle\Entity\User';
        $form["oro_entity_config_type[extend][relation][target_detailed][0]"] = 'username';
        $form["oro_entity_config_type[extend][relation][target_grid][0]"] = 'username';
        $form["oro_entity_config_type[extend][relation][target_title][0]"] = 'username';
    }

    protected function createSelectManyToOne($form)
    {
        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select required="required" name="oro_entity_config_type[extend][relation][target_field]"' .
            ' id="oro_entity_config_type_extend_relation_target_field" >' .
            '<option value="" selected="selected"></option> ' .
            '<option value="username">' .
            'Username' .
            '</option> </select> '
        );

        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form["oro_entity_config_type[extend][relation][target_entity]"] = 'Oro\Bundle\UserBundle\Entity\User';
        $form["oro_entity_config_type[extend][relation][target_field]"] = 'username';
    }
}
