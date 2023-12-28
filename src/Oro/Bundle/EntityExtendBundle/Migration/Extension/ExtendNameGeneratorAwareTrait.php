<?php

namespace Oro\Bundle\EntityExtendBundle\Migration\Extension;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

/**
 * This trait can be used by migrations that implement {@see NameGeneratorAwareInterface}
 * and need a name generator implemented by {@see ExtendDbIdentifierNameGenerator}.
 */
trait ExtendNameGeneratorAwareTrait
{
    private ExtendDbIdentifierNameGenerator $nameGenerator;

    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator): void
    {
        $this->nameGenerator = $nameGenerator;
    }
}
