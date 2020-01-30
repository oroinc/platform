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
    /**
     * @param \ArrayAccess $context
     *
     * @return DuplicatorExtensionInterface
     */
    public function setContext(\ArrayAccess $context): DuplicatorExtensionInterface;

    /**
     * @return \ArrayAccess
     */
    public function getContext(): \ArrayAccess;

    /**
     * @return Filter
     */
    public function getFilter(): Filter;

    /**
     * @return Matcher
     */
    public function getMatcher(): Matcher;

    /**
     * @param DraftableInterface $source
     *
     * @return bool
     */
    public function isSupport(DraftableInterface $source): bool;
}
