<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Extension;

use DeepCopy\Filter\Filter;
use DeepCopy\Matcher\Matcher;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;

/**
 * Interface for duplicator extensions
 */
interface DuplicatorExtensionInterface
{
    public function setContext(\ArrayAccess $context): DuplicatorExtensionInterface;

    public function getContext(): \ArrayAccess;

    public function getFilter(): Filter;

    public function getMatcher(): Matcher;

    public function isSupport(DraftableInterface $source): bool;
}
