<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

/**
 * Provides a path to a template that can be used to render a file view html block.
 */
class FilesTemplateProvider implements FilesTemplateProviderInterface
{
    private string $filesTemplate = '@OroAttachment/Twig/file.html.twig';

    public function setTemplate(string $filesTemplate): void
    {
        $this->filesTemplate = $filesTemplate;
    }

    #[\Override]
    public function getTemplate(): string
    {
        return $this->filesTemplate;
    }
}
