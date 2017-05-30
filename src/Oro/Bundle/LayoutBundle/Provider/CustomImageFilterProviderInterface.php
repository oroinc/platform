<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;

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
