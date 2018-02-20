<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\Stub;

use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;
use Oro\Bundle\ImportExportBundle\Converter\QueryBuilderAwareInterface;

interface QueryBuilderAwareDataConverter extends QueryBuilderAwareInterface, DataConverterInterface
{
}
