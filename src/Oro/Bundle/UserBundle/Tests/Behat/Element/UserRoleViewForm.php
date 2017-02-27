<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Element;

use Behat\Mink\Element\NodeElement;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\Form;

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
     * @return array
     */
    public function getPermissions()
    {
        $entityPermissions = $this->findAll('css', 'table .entity-permission-container');
        $permissionArray = [];

        /** @var NodeElement $permission */
        foreach ($entityPermissions as $permission) {
            $name = $permission->find('css', '.entity-name')->getText();
            $items = $permission->findAll('css', '.action-permissions__item');

            foreach ($items as $item) {
                $action = $item->find('css', '.action-permissions__label')->getText();
                $value = $item->find('css', '.action-permissions__value')->getText();

                if (!empty($action) && !empty($value)) {
                    $permissionArray[$name][$action] = $value;
                }
            }
        }

        return $permissionArray;
    }
}
