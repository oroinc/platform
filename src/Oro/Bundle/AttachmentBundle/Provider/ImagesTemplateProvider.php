<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

/**
 * Provides a path to a template that can be used to render an image view html block.
 */
class ImagesTemplateProvider implements ImagesTemplateProviderInterface
{
    private string $imagesTemplate = '@OroAttachment/Twig/image.html.twig';

    public function setTemplate(string $imagesTemplate): void
    {
        $this->imagesTemplate = $imagesTemplate;
    }

    public function getTemplate(): string
    {
        return $this->imagesTemplate;
    }
}
