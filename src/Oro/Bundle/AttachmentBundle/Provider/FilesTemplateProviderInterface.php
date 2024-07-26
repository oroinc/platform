<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

/**
 * Represents a service to get a path to a template that can be used to render a file view html block.
 */
interface FilesTemplateProviderInterface
{
    public function getTemplate(): string;
}
