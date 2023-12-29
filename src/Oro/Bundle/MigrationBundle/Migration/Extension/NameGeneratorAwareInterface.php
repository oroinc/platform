<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

/**
 * This interface should be implemented by migrations that depend on a database identifier name generator.
 */
interface NameGeneratorAwareInterface
{
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator);
}
