<?php

namespace Oro\Bundle\ImportExportBundle\Context;

/**
 * Defines the contract for objects that can be made aware of an import/export context.
 *
 * Classes implementing this interface can receive and store a reference to the current
 * import/export context, allowing them to access context information such as errors,
 * counters, and configuration options during processing.
 */
interface ContextAwareInterface
{
    public function setImportExportContext(ContextInterface $context);
}
