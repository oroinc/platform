<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class ConfigEntity
 *
 * @package Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages
 */
class ConfigEntity extends AbstractPageEntity
{
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $name;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $label;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $pluralLabel;
    /** @var  \PHPUnit_Extensions_Selenium2TestCase_Element */
    protected $description;

    public function init($new = false)
    {
        if ($new) {
            $this->name = $this->test->byId('oro_entity_config_type_model_className');
        }
        $this->label = $this->test->byId('oro_entity_config_type_entity_label');
        $this->pluralLabel = $this->test->byId('oro_entity_config_type_entity_plural_label');
        $this->description = $this->test->byId('oro_entity_config_type_entity_description');

        return $this;
    }

    public function setName($name)
    {
        $this->name->clear();
        $this->name->value($name);
        return $this;
    }

    public function getName()
    {
        return $this->name->value();
    }

    public function setLabel($label)
    {
        $this->label->clear();
        $this->label->value($label);
        return $this;
    }

    public function getLabel()
    {
        return $this->label->value();
    }

    public function setPluralLabel($pluralLabel)
    {
        $this->pluralLabel->clear();
        $this->pluralLabel->value($pluralLabel);
        return $this;
    }

    public function getPluralLabel()
    {
        return $this->pluralLabel->value();
    }

    public function setDescription($description)
    {
        $this->description->clear();
        $this->description->value($description);
        return $this;
    }

    public function getDescription()
    {
        return $this->description->value();
    }

    public function createField()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[@title='Create field']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function setFieldName($fieldName)
    {
        $field = $this->test->byId('oro_entity_extend_field_type_fieldName');
        $field->clear();
        $field->value($fieldName);
        return $this;
    }

    public function setType($type)
    {
        $field = $this->test->select($this->test->byId('oro_entity_extend_field_type_type'));
        $field->selectOptionByLabel($type);
        return $this;
    }

    public function edit()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Edit')]")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        $this->init();
        return $this;
    }

    public function proceed()
    {
        $this->test->byXpath("//div[@class='btn-group']/button[contains(., 'Continue')]")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function updateSchema()
    {
        $this->test->byXpath("//div[@class='pull-left btn-group icons-holder']/a[@title='Update schema']")->click();
        $this->test->byXpath("//div[@class='modal-footer']/a[contains(., 'Yes, Proceed')]")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function newCustomEntityAdd()
    {
        $this->test->byXpath("//div[@class='pull-right title-buttons-container']/a[contains(., 'Create')]")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();

        return $this;
    }

    public function checkEntityField($fieldName)
    {
        $this->assertElementPresent(
            "//div[@class='control-group']/label[normalize-space(text()) = '{$fieldName}']",
            'Custom entity field not found'
        );

        return $this;
    }
}
