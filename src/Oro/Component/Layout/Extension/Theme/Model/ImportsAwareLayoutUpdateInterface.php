<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

interface ImportsAwareLayoutUpdateInterface
{
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
