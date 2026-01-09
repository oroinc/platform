<?php

namespace Oro\Bundle\DataGridBundle\Extension\Action\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

/**
 * Defines the contract for datagrid actions.
 *
 * Actions represent operations that can be performed on datagrid rows or the entire grid.
 * Each action has a name, optional ACL resource for permission checking, and configuration
 * options that control its behavior and appearance in the UI.
 */
interface ActionInterface
{
    public const ACL_KEY = 'acl_resource';

    /**
     * Filter name
     *
     * @return string
     */
    public function getName();

    /**
     * ACL resource name
     *
     * @return string|null
     */
    public function getAclResource();

    /**
     * Action options (route, ACL resource etc.)
     *
     * @return ActionConfiguration
     */
    public function getOptions();

    /**
     * Set action options
     *
     * @param ActionConfiguration $options
     *
     * @return $this
     */
    public function setOptions(ActionConfiguration $options);
}
