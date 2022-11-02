<?php

namespace Oro\Bundle\LayoutBundle\Console\Helper;

use Oro\Bundle\LayoutBundle\Console\Descriptor\TextDescriptor;
use Symfony\Component\Console\Helper\DescriptorHelper as BaseDescriptorHelper;

/**
 * This class adds helper method to describe objects in various formats.
 */
class DescriptorHelper extends BaseDescriptorHelper
{
    public function __construct()
    {
        $this->register('txt', new TextDescriptor());
    }
}
