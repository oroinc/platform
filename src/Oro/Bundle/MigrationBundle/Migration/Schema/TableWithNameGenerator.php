<?php

namespace Oro\Bundle\MigrationBundle\Migration\Schema;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class TableWithNameGenerator extends Table
{
    /**
     * @var DbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->nameGenerator = $args['nameGenerator'];

        parent::__construct($args);
    }
}
