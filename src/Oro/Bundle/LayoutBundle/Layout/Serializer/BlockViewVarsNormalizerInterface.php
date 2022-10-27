<?php

namespace Oro\Bundle\LayoutBundle\Layout\Serializer;

/**
 * Normalizer for "vars" section of layout block view.
 */
interface BlockViewVarsNormalizerInterface
{
    /**
     * Normalizes "vars" section of layout block view.
     */
    public function normalize(array &$vars, array $context): void;

    /**
     * Denormalizes "vars" section of layout block view.
     */
    public function denormalize(array &$vars, array $context): void;
}
