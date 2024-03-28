<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Stubs;

use Oro\Bundle\ThemeBundle\Form\Configuration\ConfigurationChildBuilderInterface;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigurationChildBuilderStub implements ConfigurationChildBuilderInterface
{
    #[\Override] public static function getType(): string
    {
        return 'type';
    }

    #[\Override] public function supports(array $option): bool
    {
        return $option['type'] === self::getType();
    }

    #[\Override] public function buildOption(FormBuilderInterface $builder, array $option): void
    {
    }
}
