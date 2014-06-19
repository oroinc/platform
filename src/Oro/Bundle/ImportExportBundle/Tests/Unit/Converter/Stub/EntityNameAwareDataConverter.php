<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\Stub;

use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;

interface EntityNameAwareDataConverter extends EntityNameAwareInterface, DataConverterInterface
{
}
