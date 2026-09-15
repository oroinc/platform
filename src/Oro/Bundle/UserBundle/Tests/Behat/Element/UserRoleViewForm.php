<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;
use Oro\Bundle\UserBundle\Tests\Behat\Exception\PermissionRowNotFoundException;

class UserRoleViewForm extends Form
{
    /**
     * Fetch enabled capability permissions to array from view page form
     *
     * @return array
     */
    public function getEnabledCapabilityPermissions()
    {
        $capabilityBlocks = $this->findAll(
            'css',
            '.role-capability__item-label:not(.role-capability__item-label--no-access)'
        );
        $enabled = [];
        foreach ($capabilityBlocks as $capabilityBlock) {
            $enabled[] = $capabilityBlock->getText();
        }

        return $enabled;
    }

    /**
     * Fetch permissions to array from role view page form
     *
     * @param array $permissionNames
     * @return array
     */
    public function getPermissionsByNames(array $permissionNames)
    {
        $permissionArray = [];

        foreach ($permissionNames as $name) {
            $permission = $this->getPermissionRow($name);
            $items = $permission->findAll('xpath', '//li[contains(@class,"action-permissions__item")]');

            foreach ($items as $item) {
                $action = $item->find('xpath', '//span[@class="action-permissions__label"]')->getText();
                $value = $item->find('xpath', '//span[@class="action-permissions__value"]')->getText();

                if (!empty($action) && !empty($value)) {
                    $permissionArray[$name][$action] = $value;
                }
            }
        }

        return $permissionArray;
    }

    /**
     * Fetch permissions to array from role view page form
     *
     * @param array $permissionNames
     * @return array
     */
    public function getCustomerUserPermissionsByNames(array $permissionNames)
    {
        $permissionArray = [];

        foreach ($permissionNames as $name) {
            $permission = $this->getPermissionRow($name);
            $items = $permission->findAll('xpath', '//li[contains(@class,"action-permissions__item")]');

            foreach ($items as $item) {
                [$action, $value] = explode('-', str_replace([' - ', ' -', '- '], '-', $item->getText()));
                if ($action && $value) {
                    $permissionArray[$name][$action] = $value;
                }
            }
        }

        return $permissionArray;
    }

    /**
     * The permissions grid is rendered by the datagrid after the page has loaded, so the row is awaited
     * rather than looked up once.
     *
     * @param string $name
     * @return NodeElement
     */
    public function findPermissionRow($name): ?NodeElement
    {
        return $this->find(
            'xpath',
            sprintf(
                '//div[contains(@class, "entity-permission-container")' .
                ' and descendant::div[@class="entity-name" and text()="%s"]]',
                $name
            )
        );
    }

    /**
     * Names of the entity rows currently rendered in the permissions grid
     *
     * @return array<int, string>
     */
    public function getRenderedRowNames(): array
    {
        return array_map(
            fn (NodeElement $entityName) => $entityName->getText(),
            $this->findAll('css', '.entity-permission-container .entity-name')
        );
    }

    private function getPermissionRow($name)
    {
        $row = $this->spin(fn (self $form) => $form->findPermissionRow($name), 10);

        if (null === $row) {
            throw new PermissionRowNotFoundException($name, $this->getRenderedRowNames());
        }

        return $row;
    }
}
