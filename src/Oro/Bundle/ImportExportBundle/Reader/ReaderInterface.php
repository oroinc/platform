<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Akeneo\Bundle\BatchBundle\Item\ItemReaderInterface;
use Akeneo\Bundle\BatchBundle\Step\StepExecutionAwareInterface;

interface ReaderInterface extends ItemReaderInterface, StepExecutionAwareInterface
{
}
