<?php

namespace Oro\Bundle\UserBundle\Tests\Behat\Element;

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
     * @param array $permissionNames
     * @return array
     */
    public function getPermissionsByNames(array $permissionNames)
    {
        $permissionArray = [];

        foreach ($permissionNames as $name) {
            $permission = $this->find(
                'xpath',
                sprintf(
                    '//div[contains(@class, "entity-permission-container")'.
                    ' and descendant::div[@class="entity-name" and text()="%s"]]',
                    $name
                )
            );
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
}
