<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Selenium;

use Oro\Bundle\TestFrameworkBundle\Pages\Objects\Login;
use Oro\Bundle\TestFrameworkBundle\Test\Selenium2TestCase;

class EntityTest extends Selenium2TestCase
{
    protected $coverageScriptUrl = PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL_COVERAGE;

    protected function setUp()
    {
        $this->setHost(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_HOST);
        $this->setPort(intval(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PORT));
        $this->setBrowser(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM2_BROWSER);
        $this->setBrowserUrl(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_TESTS_URL);
    }

    protected function tearDown()
    {
        $this->cookie()->clear();
    }

    /**
     * @return string
     */
    public function testCreateEntity()
    {
        $entityName = 'Entity'.mt_rand();

        $login = new Login($this);
        $login->setUsername(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->setPassword(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS)
            ->submit()
            ->openConfigEntities()
            ->add()
            ->assertTitle('New Entity - Entities - System')
            ->setName($entityName)
            ->setLabel($entityName)
            ->setPluralLabel($entityName)
            ->save()
            ->assertMessage('Entity saved')
            ->createField()
            ->setFieldName('Test_field')
            ->setType('String')
            ->proceed()
            ->save()
            ->assertMessage('Field saved')
            ->updateSchema()
            ->close();

        return $entityName;
    }

    /**
     * @depends testCreateEntity
     * @param $entityName
     * @return string
     */
    public function testUpdateEntity($entityName)
    {
        $newEntityName = 'Update' . $entityName;
        $login = new Login($this);
        $login->setUsername(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->setPassword(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS)
            ->submit()
            ->openConfigEntities()
            //->filterBy('Label', $entityName)
            ->open(array($entityName))
            ->edit()
            ->setLabel($newEntityName)
            ->save()
            ->assertMessage('Entity saved')
            ->assertTitle($newEntityName .' - Entities - System')
            ->createField()
            ->setFieldName('Test_field2')
            ->setType('Integer')
            ->proceed()
            ->save()
            ->assertMessage('Field saved')
            ->updateSchema();

        return $newEntityName;
    }

    /**
     * @depends testUpdateEntity
     * @param $entityname
     */
    public function testEntityFieldsAvailability($entityname)
    {
        $login = new Login($this);
        $login->setUsername(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->setPassword(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS)
            ->submit()
            ->openNavigation()
            ->tab('System')
            ->menu('Entities')
            ->menu($entityname)
            ->open()
            ->openConfigEntity()
            ->newCustomEntityAdd()
            ->checkEntityField('Test_field')
            ->checkEntityField('Test_field2');
    }

    /**
     * @depends testUpdateEntity
     * @param $entityName
     */
    public function testDeleteEntity($entityName)
    {
        $login = new Login($this);
        $entityExist = $login->setUsername(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_LOGIN)
            ->setPassword(PHPUNIT_TESTSUITE_EXTENSION_SELENIUM_PASS)
            ->submit()
            ->openConfigEntities()
            //->filterBy('Label', $entityName)
            ->deleteEntity(array($entityName), 'Remove')
            ->assertMessage('Item was removed')
            ->open(array($entityName))
            ->updateSchema()
            ->close()
            ->entityExists(array($entityName));

        $this->assertFalse($entityExist);
    }
}
