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
        $target = $this->test->byXPath("//*[@data-ftid='oro_entity_config_type_extend_relation_target_entity']");
        $target = $this->test->select($target);
        $target->selectOptionByLabel($entity);
        $this->waitForAjax();
        return $this;
    }

    public function setTargetField($field)
    {
        $target = $this->test->byXPath("//*[@data-ftid='oro_entity_config_type_extend_relation_target_field']");
        $target = $this->test->select($target);
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
            $this->test->byXPath(
                "//div[contains(@class,'control-group') and contains(@class,'extend-rel-target-field')]"
                . "[div/label[normalize-space(text())='{$data}']]/div/select"
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
        $this->test->byXPath(
            "//div[contains(@class,'control-group')][div/label[normalize-space(text()) = '{$fieldName}']]" .
            "/div//button[@class='btn btn-medium add-btn']"
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
        $element = $this->test->byXPath(
            "//tr[td[normalize-space(text())='{$entityData[0]}'] and td[normalize-space(text())=" .
            "'{$entityData[1]}']]//td[contains(@class,'boolean-cell')]/input"
        );
        $this->test->moveto($element);

        $element->click();
        $this->waitForAjax();
        return $this;
    }

    public function confirmSelection()
    {
        $this->test->byXPath("//button[@data-action-name='select']")->click();
        $this->waitPageToLoad();
        $this->waitForAjax();
        return $this;
    }

    /**
     * @param $fieldName
     * @param $value
     *
     * @return $this
     */
    public function setStringField($fieldName, $value)
    {
        $field = $this->test->byXPath(
            "//div[contains(@class,'control-group')][div/label[normalize-space(text()) = '{$fieldName}']]/div/input"
        );
        $field->clear();
        $field->value($value);
        return $this;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function addMultiSelectOptions($options = array())
    {
        // $flag used for counting adding new options to Multi Select field
        $flag = 1;
        foreach ($options as $option) {
            $field = $this->test
                ->byXPath(
                    "(//input[contains(@data-ftid,'oro_entity_config_type_enum_enum_options')" .
                    " and @type='text'])[{$flag}]"
                );
            $field->clear();
            $field->value($option);
            if ($flag < count($options)) {
                $this->test->byXPath(
                    "//div[contains(@class,'control-group-collection')]//a[normalize-space(text()) = 'Add']"
                )->click();
                $this->waitForAjax();
                $flag++;
            }
        }
        return $this;
    }
}
