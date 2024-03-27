<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Sandbox;

/**
 * Creates {@see EntityFormatExtension} TWIG extension for sandbox environment.
 */
class EntityFormatExtensionFactory
{
    private TemplateRendererConfigProviderInterface $templateRendererConfigProvider;

    public function __construct(TemplateRendererConfigProviderInterface $templateRendererConfigProvider)
    {
        $this->templateRendererConfigProvider = $templateRendererConfigProvider;
    }

    public function __invoke(): EntityFormatExtension
    {
        $templateRendererConfig = $this->templateRendererConfigProvider->getConfiguration();

        $extension = new EntityFormatExtension();
        $extension->setFormatters(
            $templateRendererConfig[TemplateRendererConfigProviderInterface::DEFAULT_FORMATTERS] ?? []
        );

        return $extension;
    }
}
