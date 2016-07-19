<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class UserRoleForm extends Form
{
    /**
     * @param string $entity Entity name e.g. Account, Business Customer, Comment etc.
     * @param string $action e.g. Create, Delete, Edit, View etc.
     * @param string $accessLevel e.g. System, None, User etc.
     *
     * @throws ExpectationException
     */
    public function setPermission($entity, $action, $accessLevel)
    {
        $entityRow = $this->getEntityRow($entity);
        $actionRow = $this->getActionRow($entityRow, $action);
        /** todo: Move waitForAjax to driver. BAP-10843 */
        sleep(1);
        $levels = $actionRow->findAll('css', 'ul.dropdown-menu-collection__list li a');

        /** @var NodeElement $level */
        foreach ($levels as $level) {
            if (preg_match(sprintf('/%s/i', $accessLevel), $level->getText())) {
                $level->mouseOver();
                $level->click();
                return;
            }
        }

        throw new ExpectationException(
            sprintf('There is no "%s" entity row', $accessLevel),
            $this->session->getDriver()
        );
    }

    /**
     * @param NodeElement $entityRow
     * @param string $action
     * @return NodeElement
     * @throws ExpectationException
     */
    protected function getActionRow(NodeElement $entityRow, $action)
    {
        /** @var NodeElement $label */
        foreach ($entityRow->findAll('css', 'span.action-permissions__label') as $label) {
            if (preg_match(sprintf('/%s/i', $action), $label->getText())) {
                $label->click();

                return $label->getParent()->getParent()->find('css', 'div.dropdown-menu');
            }
        }

        throw new ExpectationException(
            sprintf('There is no "%s" action', $action),
            $this->session->getDriver()
        );
    }

    /**
     * @param string $entity
     * @return NodeElement
     * @throws ExpectationException
     */
    protected function getEntityRow($entity)
    {
        $entityTrs = $this->findAll("css", "div[id^=grid-role-permission-grid] table.grid tr.grid-row");

        /** @var NodeElement $entityTr */
        foreach ($entityTrs as $entityTr) {
            if (false !== strpos($entityTr->find('css', 'td.grid-body-cell-entity')->getText(), $entity)) {
                return $entityTr;
            }
        }

        throw new ExpectationException(
            sprintf('There is no "%s" entity row', $entity),
            $this->session->getDriver()
        );
    }
}
