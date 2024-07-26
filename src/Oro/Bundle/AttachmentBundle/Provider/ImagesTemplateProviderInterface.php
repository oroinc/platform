<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

/**
 * Represents a service to get a path to a template that can be used to render an image view html block.
 */
interface ImagesTemplateProviderInterface
{
    public function getTemplate(): string;
}
