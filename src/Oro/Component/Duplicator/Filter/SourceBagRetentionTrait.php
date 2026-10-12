<?php

declare(strict_types=1);

namespace Oro\Component\Duplicator\Filter;

use Oro\Component\Duplicator\PropertyBagInterface;

/**
 * Keeps the source bags alive while the filter lives: DeepCopy tracks copied objects by spl_object_hash,
 * and a freed bag would let a later object reuse its hash.
 */
trait SourceBagRetentionTrait
{
    private ?\SplObjectStorage $retainedSourceBags = null;

    private function retainSourceBag(PropertyBagInterface $bag): void
    {
        $this->retainedSourceBags ??= new \SplObjectStorage();
        $this->retainedSourceBags->offsetSet($bag);
    }
}
