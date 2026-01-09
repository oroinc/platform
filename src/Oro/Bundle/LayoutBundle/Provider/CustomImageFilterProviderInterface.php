<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;

/**
 * Defines the contract for custom image filter providers.
 *
 * Implementations of this interface provide custom image filter configurations
 * and determine whether a filter is applicable to specific image dimensions.
 * This allows themes and extensions to register custom image processing filters
 * that can be selectively applied based on image type dimensions.
 */
interface CustomImageFilterProviderInterface
{
    /**
     * @return array
     */
    public function getFilterConfig();

    /**
     * @param ThemeImageTypeDimension $dimension
     * @return bool
     */
    public function isApplicable(ThemeImageTypeDimension $dimension);
}
