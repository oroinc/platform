<?php

namespace Oro\Bundle\NavigationBundle\Annotation;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationAnnotation;

/**
 * Title service annotation parser
 * @package Oro\Bundle\NavigationBundle\Annotation
 * @Annotation
 * @Target({"METHOD"})
 */
class TitleTemplate extends ConfigurationAnnotation
{
    /**
     * @var string
     */
    private $value;

    /**
     * Returns annotation data
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
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
