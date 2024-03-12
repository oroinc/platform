<?php

namespace Oro\Bundle\NavigationBundle\Attribute;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Title service attribute parser
 * @package Oro\Bundle\NavigationBundle\Attribute
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class TitleTemplate extends ConfigurationAnnotation
{
    public function __construct(private string $value)
    {
        parent::__construct(['value' => $value]);
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

    /**
     * {@inheritdoc}
     */
    public function getAliasName()
    {
        return 'title_template';
    }

    /**
     * {@inheritdoc}
     */
    public function allowArray()
    {
        return false;
    }
}
