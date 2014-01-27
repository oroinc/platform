<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageEntity;

/**
 * Class CustomEntity
 *
 * @package Oro\Bundle\EntityConfigBundle\Tests\Selenium\Pages
 */
class CustomEntity extends AbstractPageEntity
{
    public function setTargetEntity($entity)
    {
        $target = $this->test->select($this->test->byId('oro_entity_config_type_extend_relation_target_entity'));
        $target->selectOptionByLabel($entity);
        $this->waitForAjax();
        return $this;
    }

    public function setTargetField($field)
    {
        $target = $this->test->select($this->test->byId('oro_entity_config_type_extend_relation_target_field'));
        $target->selectOptionByLabel($field);
        $this->waitForAjax();
        return $this;
    }

    /**
     * @param $data
     * @param $fields
     * @return $this
     */
    public function setRelation($data, $fields)
    {
        $relation = $this->test->select(
            $this->test->byXpath(
                "//div[@class='control-group extend-rel-target-field']/label[normalize-space(text())=" .
                "'{$data}']/following-sibling::div/select"
            )
        );
        foreach ($fields as $field) {
            $relation->selectOptionByLabel($field);
        }
        return $this;
    }

    /**
     * @param $fieldName
     * @return $this
     */
    public function addRelation($fieldName)
    {
        $this->test->byXpath(
            "//div[@class='control-group']/label[normalize-space(text()) = " .
            "'{$fieldName}']/following-sibling::div//button[@class='btn btn-medium add-btn']"
        )->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    /**
     * @param array $entityData
     * @return $this
     */
    public function selectEntity($entityData = array())
    {
        $this->test->byXpath(
            "//tr[td[normalize-space(text())='{$entityData[0]}'] and td[normalize-space(text())=" .
            "'{$entityData[1]}']]//td[@class='boolean-cell']/input"
        )->click();
        $this->waitForAjax();
        return $this;
    }

    public function confirmSelection()
    {
        $this->test->byXpath("//button[@data-action-name='select']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    public function setStringField($fieldname, $value)
    {
        $field = $this->test->byXpath(
            "//div[@class='control-group']/label[normalize-space(text()) = '{$fieldname}']" .
            "/following-sibling::div/input"
        );
        $field->clear();
        $field->value($value);
        return $this;
    }


    public function addOptions($options = array())
    {
        // $flag used for counting adding new options to Option Set field
        $flag = 0;
        foreach ($options as $option) {
            $field = $this->test->byId("oro_entity_config_type_extend_set_options_{$flag}_label");
            $field->clear();
            $field->value($option);
            if ($flag < count($options)-1) {
                $this->test->byXpath("//div[@class='control-group']//a[normalize-space(text()) = 'Add']")->click();
                $this->waitForAjax();
                $flag++;
            }
        }
        return $this;
    }

    public function setOptionSetField()
    {

    }
}
