<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

/**
 * NameGeneratorAwareInterface should be implemented by extensions that depends on a database identifier name generator.
 */
interface NameGeneratorAwareInterface
{
    /**
     * Sets the database identifier name generator
     *
     * @param DbIdentifierNameGenerator $nameGenerator
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator);
}
