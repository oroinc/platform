<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

class UserRoleForm extends Form
{
    /**
     * @param string $entity
     * @param string $action
     * @param string $accessLevel
     * @throws ExpectationException
     */
    public function setPermission($entity, $action, $accessLevel)
    {
        $entityRow = $this->getEntityRow($entity);
        $actionRow = $this->getActionRow($entityRow, $action);
        $actionRow->find('css', 'div.access_level_value a')->click();
        /** todo: Move waitForAjax to driver. BAP-10843 */
        sleep(1);
        $levels = $this->getPage()->findAll('css', '#select2-drop ul.select2-results li div');

        /** @var NodeElement $level */
        foreach ($levels as $level) {
            if (false !== strpos($level->getText(), $accessLevel)) {
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
        /** @var NodeElement $tr */
        foreach ($entityRow->findAll('css', 'tr') as $tr) {
            if (false !== strpos($tr->find('css', 'td')->getText(), $action)) {
                return $tr;
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
        $entityCells = $this->findAll("css", "div[id^='oro_user_role_form_entity']");

        /** @var NodeElement $entityCell */
        foreach ($entityCells as $entityCell) {
            if (false !== strpos($entityCell->getText(), $entity)) {
                $parent = $entityCell->getParent();

                while ('tr' !== $parent->getTagName()) {
                    $parent = $parent->getParent();
                }

                return $parent;
            }
        }

        throw new ExpectationException(
            sprintf('There is no "%s" entity row', $entity),
            $this->session->getDriver()
        );
    }
}
