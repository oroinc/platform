<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityExtendBundle\Cache\EntityCacheWarmer;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;

abstract class AbstractConfigControllerTest extends WebTestCase
{
    /**
     * @var \Closure
     */
    protected static $warmupCache;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(false);

        self::warmUpEntityCache();
    }

    protected function warmUpEntityCache()
    {
        // These tests breaks isolation between tests by modifying the cache.
        // It leads to an exception in tests run after these ones.
        // Internal Server Error: A model for "Extend\Entity\testExtendedEntity" was not found.
        /** @var EntityCacheWarmer $entityCacheWarmup */
        $entityCacheWarmup = $this->getContainer()->get('oro_entity_extend.entity.cache.warmer');
        $cacheDir = $this->client->getKernel()->getCacheDir();
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

    /**
     * @return string
     */
    protected function getTestEntityAlias()
    {
        return $this->getContainer()
            ->get('oro_entity.entity_alias_resolver')
            ->getAlias(TestActivityTarget::class);
    }

    /**
     * @param \Symfony\Component\DomCrawler\Form $form
     */
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
        $form["oro_entity_config_type[extend][relation][target_entity]"] = User::class;
        $form["oro_entity_config_type[extend][relation][target_detailed][0]"] = 'username';
        $form["oro_entity_config_type[extend][relation][target_grid][0]"] = 'username';
        $form["oro_entity_config_type[extend][relation][target_title][0]"] = 'username';
    }

    /**
     * @param \Symfony\Component\DomCrawler\Form $form
     */
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
        $form["oro_entity_config_type[extend][relation][target_entity]"] = User::class;
        $form["oro_entity_config_type[extend][relation][target_field]"] = 'username';
    }

    /**
     * @param \Symfony\Component\DomCrawler\Form $form
     */
    protected function createManyToOneNonExtendableEntitySelect($form)
    {
        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select required="required" name="oro_entity_config_type[extend][relation][target_field]"' .
            ' id="oro_entity_config_type_extend_relation_target_field" >' .
            '<option value="" selected="selected"></option> ' .
            '<option value="id">' .
            'Entity fallback value' .
            '</option> </select> '
        );

        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
        $form["oro_entity_config_type[extend][relation][target_entity]"] = EntityFieldFallbackValue::class;
        $form["oro_entity_config_type[extend][relation][target_field]"] = 'id';
    }

    /**
     * @param \Symfony\Component\DomCrawler\Form $form
     */
    protected function createBidirectionalSelect($form)
    {
        $doc = new \DOMDocument("1.0");
        $doc->loadHTML(
            '<select required="required" name="oro_entity_config_type[extend][relation][bidirectional]"' .
            ' id="oro_entity_config_type_extend_relation_bidirectional" >' .
            '    <option value="0" selected="selected">No</option> ' .
            '    <option value="1">Yes</option> ' .
            '</select> '
        );

        $field = new ChoiceFormField($doc->getElementsByTagName('select')->item(0));
        $form->set($field);
    }
}
