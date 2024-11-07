<?php

namespace Oro\Bundle\ThemeBundle\Provider;

/**
 * Abstraction for theme configuration type providers.
 */
interface ThemeConfigurationTypeProviderInterface
{
    public function getType(): string;

    public function getLabel(): string;
}
