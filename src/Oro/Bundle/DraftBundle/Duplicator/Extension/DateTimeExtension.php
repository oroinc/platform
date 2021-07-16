<?php

namespace Oro\Bundle\DraftBundle\Duplicator\Extension;

use DeepCopy\Filter\Filter;
use DeepCopy\Matcher\Matcher;
use DeepCopy\Matcher\PropertyTypeMatcher;
use Oro\Bundle\DraftBundle\Duplicator\Filter\DateTimeFilter;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Manager\DraftManager;

/**
 * Responsible for copying behavior of DateTime type parameters.
 */
class DateTimeExtension extends AbstractDuplicatorExtension
{
    public function getFilter(): Filter
    {
        return new DateTimeFilter();
    }

    public function getMatcher(): Matcher
    {
        return new PropertyTypeMatcher(\DateTime::class);
    }

    /**
     * @inheritDoc
     */
    public function isSupport(DraftableInterface $source): bool
    {
        return $this->getContext()->offsetGet('action') === DraftManager::ACTION_CREATE_DRAFT;
    }
}
