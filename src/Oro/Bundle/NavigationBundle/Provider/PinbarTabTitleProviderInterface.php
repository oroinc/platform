<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\NavigationBundle\Entity\AbstractNavigationItem;
use Oro\Bundle\NavigationBundle\Entity\PinbarTab;

/**
 * Interface for classes which provide title and short title for PinarTab entity.
 */
interface PinbarTabTitleProviderInterface
{
    /**
     * @param AbstractNavigationItem $navigationItem
     * @param string $className PinbatTab entity class name
     *
     * @return array
     *  ['<title>', 'shortTitle']
     */
    public function getTitles(AbstractNavigationItem $navigationItem, string $className = PinbarTab::class): array;
}
