<?php

namespace Oro\Bundle\AttachmentBundle\Imagine\Cache\Resolver;

use Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver;

class CustomWebPathResolver extends WebPathResolver
{
    /**
     * {@inheritdoc}
     */
    protected function getFileUrl($path, $filter)
    {
        return ltrim($path, '/');
    }
}
