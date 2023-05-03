<?php

namespace Oro\Bundle\DraftBundle\Manager;

use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Responsible for move data from draft entity to source entity, including only draftable fields.
 */
class Publisher
{
    /**
     * @var DraftHelper
     */
    private $draftHelper;

    public function __construct(DraftHelper $draftHelper)
    {
        $this->draftHelper = $draftHelper;
    }

    public function create(DraftableInterface $source): DraftableInterface
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        $target = $source->getDraftSource();
        $properties = $this->draftHelper->getDraftableProperties($source);
        foreach ($properties as $property) {
            $value = $accessor->getValue($source, $property);
            $accessor->setValue($target, new PropertyPath($property), $value);
        }

        return $target;
    }
}
