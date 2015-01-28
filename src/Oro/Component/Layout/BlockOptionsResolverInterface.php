<?php

namespace Oro\Component\Layout;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

interface BlockOptionsResolverInterface
{
    /**
     * Returns the combination of the default options for the given block type and the passed options
     *
     * @param string|BlockTypeInterface $blockType The block type
     * @param array                     $options   The custom option values.
     *
     * @return array A list of options and their values
     *
     * @throws InvalidOptionsException if any given option is not applicable to the given block type
     */
    public function resolve($blockType, array $options = []);
}
