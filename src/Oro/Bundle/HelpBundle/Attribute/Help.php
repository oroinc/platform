<?php

namespace Oro\Bundle\HelpBundle\Attribute;

use Attribute;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

/**
 * The attribute for setting the tooltips for entity form fields.
 */
#[Attribute()]
class Help implements ConfigurationInterface
{
    public const ALIAS = 'oro_help';

    public function __construct(
        protected ?string $vendorAlias = null,
        protected ?string $bundleAlias = null,
        protected ?string $controllerAlias = null,
        protected ?string $actionAlias = null,
        protected ?string $link = null,
        protected ?string $prefix = null,
        protected ?string $server = null,
        protected ?string $uri = null,
    ) {
    }

    public function getConfigurationArray(): array
    {
        $optionsMap = [
            'vendorAlias' => 'vendor',
            'bundleAlias' => 'bundle',
            'controllerAlias' => 'controller',
            'actionAlias' => 'action',
            'link' => 'link',
            'prefix' => 'prefix',
            'server' => 'server',
            'uri' => 'uri',
        ];

        $configuration = [];
        foreach ($optionsMap as $property => $key) {
            if (isset($this->$property)) {
                $configuration[$key] = $this->$property;
            }
        }
        return $configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function allowArray(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getAliasName(): string
    {
        return static::ALIAS;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getServer(): string
    {
        return $this->server;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getControllerAlias(): string
    {
        return $this->controllerAlias;
    }

    public function getActionAlias(): string
    {
        return $this->actionAlias;
    }

    public function getBundleAlias(): string
    {
        return $this->bundleAlias;
    }

    public function getVendorAlias(): string
    {
        return $this->vendorAlias;
    }
}
