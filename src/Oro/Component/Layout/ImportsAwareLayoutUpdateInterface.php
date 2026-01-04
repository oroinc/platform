<?php

namespace Oro\Component\Layout;

interface ImportsAwareLayoutUpdateInterface
{
    public const ID_KEY = 'id';
    public const ROOT_KEY = 'root';
    public const NAMESPACE_KEY = 'namespace';

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
