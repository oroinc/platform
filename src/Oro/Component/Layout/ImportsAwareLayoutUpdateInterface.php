<?php

namespace Oro\Component\Layout;

/**
 * Defines the contract for layout updates that support importing other layout updates.
 *
 * Implementations of this interface can declare imports of other layout updates, specifying
 * the import ID, root block, and namespace for each imported layout update.
 */
interface ImportsAwareLayoutUpdateInterface
{
    const ID_KEY = 'id';
    const ROOT_KEY = 'root';
    const NAMESPACE_KEY = 'namespace';

    /**
     * Should return array of arrays with import id, root and namespace
     * [
     *   'id' => 'import_id',
     *   'root' => 'root_block_id',
     *   'namespace' => 'import_namespace',
     * ]
     * @return array
     */
    public function getImports();
}
