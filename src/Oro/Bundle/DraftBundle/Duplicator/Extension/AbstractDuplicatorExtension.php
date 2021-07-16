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

    /**
     * @inheritDoc
     */
    public function setContext(\ArrayAccess $context): DuplicatorExtensionInterface
    {
        $this->context = $context;

        return $this;
    }

    public function getContext(): \ArrayAccess
    {
        return $this->context;
    }

    /**
     * @inheritDoc
     */
    public function isSupport(DraftableInterface $source): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    abstract public function getFilter(): Filter;

    /**
     * @inheritDoc
     */
    abstract public function getMatcher(): Matcher;
}
