<?php

namespace Oro\Bundle\EntityBundle\Provider;

use Oro\Bundle\UIBundle\Model\Image;

interface EntityAvatarProviderInterface
{
    /**
     * @param string Liip imagine filter name
     * @param object
     *
     * @return Image
     */
    public function getAvatarImage($filterName, $entity);
}
