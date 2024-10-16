<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Extension;

use DeepCopy\Filter\Filter;
use DeepCopy\Matcher\Matcher;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;

/**
 * Base abstract class for duplicator extensions.
 */
abstract class AbstractDuplicatorExtension implements DuplicatorExtensionInterface
{
    /**
     * @var \ArrayAccess
     */
    private $context;

    #[\Override]
    public function setContext(\ArrayAccess $context): DuplicatorExtensionInterface
    {
        $this->context = $context;

        return $this;
    }

    #[\Override]
    public function getContext(): \ArrayAccess
    {
        return $this->context;
    }

    #[\Override]
    public function isSupport(DraftableInterface $source): bool
    {
        return true;
    }

    #[\Override]
    abstract public function getFilter(): Filter;

    #[\Override]
    abstract public function getMatcher(): Matcher;
}
