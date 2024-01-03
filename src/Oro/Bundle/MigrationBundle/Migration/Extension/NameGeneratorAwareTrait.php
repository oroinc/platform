<?php

namespace Oro\Bundle\MigrationBundle\Migration\Extension;

use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

/**
 * This trait can be used by migrations that implement {@see NameGeneratorAwareInterface}.
 */
trait NameGeneratorAwareTrait
{
    private DbIdentifierNameGenerator $nameGenerator;

    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator): void
    {
        $this->nameGenerator = $nameGenerator;
    }
}
