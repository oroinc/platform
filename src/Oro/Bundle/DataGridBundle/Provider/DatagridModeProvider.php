<?php

namespace Oro\Bundle\DataGridBundle\Provider;

/**
 * Stores datagrid modes which can help to filter what extensions to load in different modes
 */
class DatagridModeProvider
{
    public const DATAGRID_FRONTEND_MODE     = 'frontend';
    public const DATAGRID_BACKEND_MODE      = 'backend';
    public const DATAGRID_IMPORTEXPORT_MODE = 'importexport';
}
