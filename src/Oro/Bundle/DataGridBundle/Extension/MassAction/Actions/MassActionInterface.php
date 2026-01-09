<?php

namespace Oro\Bundle\DataGridBundle\Extension\MassAction\Actions;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\ActionInterface;

/**
 * Defines the contract for mass actions in datagrids.
 *
 * Mass actions are operations that can be performed on multiple datagrid rows simultaneously,
 * such as bulk delete, bulk update, or bulk export. They extend the basic {@see ActionInterface}
 * with capabilities for handling multiple selected records.
 */
interface MassActionInterface extends ActionInterface
{
}
