<?php

namespace Oro\Component\Layout;

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
