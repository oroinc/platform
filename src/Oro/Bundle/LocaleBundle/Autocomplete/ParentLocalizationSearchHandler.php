<?php

namespace Oro\Bundle\LocaleBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\AbstractParentEntitySearchHandler;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class ParentLocalizationSearchHandler extends AbstractParentEntitySearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function getChildrenIds($localization)
    {
        $ids = [];

        if ($localization instanceof Localization && !$localization->getChilds()->isEmpty()) {
            foreach ($localization->getChilds() as $child) {
                $childrenIds = $this->getChildrenIds($child);

                foreach ($childrenIds as $id) {
                    $ids[] = $id;
                }

                $ids[] = $child->getId();
            }
        }

        return array_unique($ids);
    }
}
