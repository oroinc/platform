<?php

namespace Oro\Bundle\PlatformBundle\Interface;

/**
 * PHP Attribute Configuration Interface that provide configuration to the request attributes.
 *
 * This interface serves as a marker for custom PHP attributes that should be automatically
 * processed by the {@see \Oro\Bundle\PlatformBundle\EventListener\Controller\ControllerListener}.
 * It replaces the functionality previously
 * provided by Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface.
 *
 * When you create a PHP attribute and implement this interface, the attribute will be:
 * - Automatically detected on controller classes and methods
 * - Processed by the ControllerListener during the kernel.controller event
 * - Made available for configuration and request modification
 */
interface PHPAttributeConfigurationInterface
{
    /**
     * Returns the alias name for an attributed configuration
     */
    public function getAliasName(): string;

    /**
     * Returns whether multiple attributes of this type are allowed
     */
    public function allowArray(): bool;
}
