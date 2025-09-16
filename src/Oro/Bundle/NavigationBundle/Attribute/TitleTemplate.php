<?php

namespace Oro\Bundle\NavigationBundle\Attribute;

use Oro\Bundle\PlatformBundle\Interface\PHPAttributeConfigurationInterface;

/**
 * Title service attribute parser
 * @package Oro\Bundle\NavigationBundle\Attribute
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class TitleTemplate implements PHPAttributeConfigurationInterface
{
    public function __construct(private string $value)
    {
    }

    /**
     * Returns annotation data
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value)
    {
        $this->value = $value;
    }

    #[\Override]
    public function getAliasName(): string
    {
        return 'title_template';
    }

    #[\Override]
    public function allowArray(): bool
    {
        return false;
    }
}
