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
     */
    public function setPermission($entity, $action, $accessLevel)
    {
        $entityRow = $this->getEntityRow($entity);
        $actionRow = $this->getActionRow($entityRow, $action);
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
     * @param NodeElement $entityRow
     * @param string $action
     * @return NodeElement
     */
    protected function getActionRow(NodeElement $entityRow, $action)
    {
        /** @var NodeElement $label */
        foreach ($entityRow->findAll('css', 'span.action-permissions__label') as $label) {
            if (preg_match(sprintf('/%s/i', $action), $label->getText())) {
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
     * @return NodeElement
     */
    protected function getEntityRow($entity)
    {
        $entityTrs = $this->findAll('css', 'div[id*=permission-grid].inner-permissions-grid table.grid tbody tr');
        self::assertNotCount(0, $entityTrs, 'Can\'t find table with permissions on the page');

        /** @var NodeElement $entityTr */
        foreach ($entityTrs as $entityTr) {
            if ($entityTr->find('css', 'td div.entity-name')->getText() === $entity) {
                return $entityTr;
            }
        }

        self::fail(sprintf('There is no "%s" entity row', $entity));
    }
}
