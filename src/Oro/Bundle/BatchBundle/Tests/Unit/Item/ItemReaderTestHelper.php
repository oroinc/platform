<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Item;

use Oro\Bundle\BatchBundle\Item\AbstractConfigurableStepElement;
use Oro\Bundle\BatchBundle\Item\ItemReaderInterface;

/**
 * Test helper class needed as there is no way to create a phpunit mock that extends and implements as the same time.
 */
abstract class ItemReaderTestHelper extends AbstractConfigurableStepElement implements ItemReaderInterface
{
}
