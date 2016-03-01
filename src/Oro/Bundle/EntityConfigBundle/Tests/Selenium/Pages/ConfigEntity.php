<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages;

/**
 * Class ConfigEntity
 *
 * @package Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages
 * @method ConfigEntity openConfigEntity(string $bundlePath)
 */
class ConfigEntity extends CustomEntity
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
            $this->name = $this->test->byXPath("//*[@data-ftid='oro_entity_config_type_model_className']");
        }
        $this->label = $this->test->byXPath("//*[@data-ftid='oro_entity_config_type_entity_label']");
        $this->pluralLabel = $this->test->byXPath("//*[@data-ftid='oro_entity_config_type_entity_plural_label']");
        $this->description = $this->test->byXPath("//*[@data-ftid='oro_entity_config_type_entity_description']");

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
        $this->test->byXPath("//div[@class='pull-left btn-group icons-holder']/a[@title='Create field']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function setFieldName($fieldName)
    {
        $field = $this->test->byXPath("//*[@data-ftid='oro_entity_extend_field_type_fieldName']");
        $field->clear();
        $field->value($fieldName);
        return $this;
    }

    public function setType($type)
    {
        $field = $this->test->select($this->test->byXPath("//*[@data-ftid='oro_entity_extend_field_type_type']"));
        $field->selectOptionByLabel($type);
        return $this;
    }

    public function setStorageType($type)
    {
        $field = $this->test
            ->select($this->test->byXPath("//*[@data-ftid='oro_entity_extend_field_type_is_serialized']"));
        $field->selectOptionByLabel($type);
        return $this;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function enableNotes($value = 'Yes')
    {
        $field = $this->test
            ->select($this->test->byXPath("//*[@data-ftid='oro_entity_config_type_note_enabled']"));
        $field->selectOptionByLabel($value);
        return $this;
    }

    /**
     * Method set activity On if it is not
     * @param array $activities
     * @return $this
     */
    public function setActivitiesOn($activities = array())
    {
        foreach ($activities as $activity) {
            $xpath = "//div[@data-ftid='oro_entity_config_type_activity_activities']" .
                "//label[contains(., '{$activity}')]";
            if (!($this->isElementPresent($xpath."/preceding-sibling::input[@checked='checked']"))) {
                $this->test->byXPath($xpath."/preceding-sibling::input")->click();
            }
        }
        return $this;
    }

    public function edit()
    {
        $this->test->byXPath("//div[@class='pull-left btn-group icons-holder']/a[contains(., 'Edit')]")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        $this->init();
        return $this;
    }

    public function proceed()
    {
        $this->test->byXPath("//div[@class='btn-group']/button[contains(., 'Continue')]")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function updateSchema()
    {
        $this->test->byXPath("//div[@class='pull-left btn-group icons-holder']/a[@title='Update schema']")->click();
        $this->test->byXPath("//div[@class='modal-footer']/a[contains(., 'Yes, Proceed')]")->click();
        $this->waitForAjax();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function newCustomEntityAdd()
    {
        $this->test->byXPath("//div[@class='pull-right title-buttons-container']/a[contains(., 'Create')]")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    /**
     * @param string $fieldName
     * @return $this
     */
    public function checkEntityField($fieldName)
    {
        //label
        $this->assertElementPresent(
            "//label[contains(@for,'{$fieldName}-uid') and contains(text(),'{$fieldName}')]",
            "Custom entity field label not found : {$fieldName}"
        );
        //input
        $this->assertElementPresent(
            "//div[@class='controls']//*[contains(@id,'{$fieldName}-uid')]",
            "Custom entity field input not found : {$fieldName}"
        );
        return $this;
    }

    /**
     * @param string $fieldName
     * @param string $value
     * @return $this
     */
    public function setCustomField($fieldName, $value)
    {
        $fields = $this->test->elements($this->test->using('xpath')
            ->value("//div/input[contains(@id, '{$fieldName}')]"));
        if (count($fields) === 1) {
            $field = $this->test->byXPath("//div/input[contains(@id, '{$fieldName}')]");
            $field->clear();
            $field->value($value);
        } else {
            if ($this->isElementPresent("//select[contains(@id, '{$fieldName}')]")) {
                $field = $this->test->select(
                    $this->test->byXPath("//select[contains(@id, '{$fieldName}')]")
                );
                $field->selectOptionByLabel($value);
            } else {
                if ($this->isElementPresent("//textarea[contains(@id, '{$fieldName}')]")) {
                    $field = $this->test->byXPath(
                        "//textarea[contains(@id, '{$fieldName}')]"
                    );
                    $field->clear();
                    $field->value($value);
                } else {
                    $dateField = "[contains(@id, '{$fieldName}') and contains(@id, 'date_selector_')]";
                    $timeField = "[contains(@id, '{$fieldName}') and contains(@id, 'time_selector_')]";

                    if (preg_match('/^(.+\d{4}),?\s(\d{1,2}\:\d{2}\s\w{2})$/', $value, $valueParts)
                        and $this->isElementPresent("//input{$dateField}")
                        and $this->isElementPresent("//input{$timeField}")
                    ) {
                        $field = $this->test->byXPath("//input{$dateField}");
                        $field->click(); // focus
                        $field->clear();
                        $field->value($valueParts[1]);

                        $field = $this->test->byXPath("//input{$timeField}");
                        $field->click(); // focus
                        $field->clear();
                        $field->value($valueParts[2]);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param $fieldName
     * @param array $options
     * @return $this
     */
    public function setMultiSelectField($fieldName, $options = array())
    {
        foreach ($options as $option) {
            $this->test->byXPath(
                "//div[div/label[text() = '{$fieldName}']]".
                "//label[contains(., '{$option}')]"
            )->click();
        }

        return $this;
    }
}
