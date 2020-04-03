<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class UserRoleForm extends Form
{
    /**
     * @param string $entity Entity name e.g. Account, Business Customer, Comment etc.
     * @param string $action e.g. Create, Delete, Edit, View etc.
     * @param string $accessLevel e.g. System, None, User etc.
     * @param bool $field is field permission should be changed.
     */
    public function setPermission($entity, $action, $accessLevel, $field = false)
    {
        $entityRows = $field ? $this->getEntityFieldRows($entity) : $this->getEntityRows($entity);
        $actionRow = $this->getActionRow($entityRows, $action);
        $this->getDriver()->waitForAjax();
        $levels = $actionRow->findAll('css', '.dropdown-menu li a');
        $availableLevels = [];

        /** @var NodeElement $level */
        foreach ($levels as $level) {
            $levelCaption = strip_tags($level->getHtml());
            $availableLevels[] = $levelCaption;

            if (preg_match(sprintf('/%s/i', preg_quote($accessLevel, '\\')), $levelCaption)) {
                $level->mouseOver();
                $level->click();
                return;
            }
        }

        self::fail(sprintf(
            'Entity "%s" has no "%s" access level to choose. Available levels "%s"',
            $entity,
            $accessLevel,
            implode(',', $availableLevels)
        ));
    }

    /**
     * Checks capability permission checkbox
     *
     * @param string $name
     * @param bool $check
     */
    public function setCheckBoxPermission($name, $check = true)
    {
        $label = $this->findVisible('css', $this->selectorManipulator->addContainsSuffix('label', $name));
        $element = $label->find('css', 'input');

        if ($check) {
            $element->check();
        } else {
            $element->uncheck();
        }
    }

    /**
     * @param NodeElement[]|array $entityRows
     * @param string $action
     * @return NodeElement
     */
    protected function getActionRow(array $entityRows, $action)
    {
        foreach ($entityRows as $entityRow) {
            // Case-insensitive search for action containing given $action text
            $label = $entityRow->find(
                'xpath',
                '//span[@class="action-permissions__label"]' .
                '[contains(' .
                    'translate(text(),"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),' .
                     '"'.strtolower($action).'"' .
                ')]'
            );
            if ($label) {
                $label->click();

                $dropDown = $this->getPage()->findVisible('css', '.dropdown-menu__permissions-item.show');
                self::assertNotNull($dropDown, "Visible permission list dropdown not found for $action");

                return $dropDown;
            }
        }

        self::fail(sprintf('There is no "%s" action', $action));
    }

    /**
     * @param string $entity
     * @return NodeElement[]
     */
    protected function getEntityRows($entity)
    {
        // Find TR element which contains element div.entity-name with text $entity
        $entityTrs = $this->findAll('xpath', "//div[contains(@class,'entity-name')][text()='$entity']/ancestor::tr");
        self::assertNotCount(0, $entityTrs, sprintf('There is no "%s" entity row', $entity));

        return $entityTrs;
    }

    /**
     * Find parent div element which contains element div.field-name with text $field.
     *
     * @param string $field
     *
     * @return NodeElement[]
     */
    protected function getEntityFieldRows($field)
    {
        $fieldTrs = $this->findAll(
            'xpath',
            "//div[contains(@class,'field-name')][text()='$field']"
            . "/ancestor::div[contains(@class, 'field-permission-container')]"
        );
        self::assertNotCount(0, $fieldTrs, sprintf('There is no "%s" field row', $field));

        return $fieldTrs;
    }
}
