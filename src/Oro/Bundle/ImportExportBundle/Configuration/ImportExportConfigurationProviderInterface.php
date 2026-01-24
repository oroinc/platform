<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

/**
 * Defines the contract for providing import/export configuration.
 *
 * Implementations of this interface are responsible for supplying configuration
 * details for import and export operations, including processor aliases, entity names,
 * and other settings required to execute import/export jobs.
 */
interface ImportExportConfigurationProviderInterface
{
    public function get(): ImportExportConfigurationInterface;
}
