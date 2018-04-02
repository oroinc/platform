<?php

namespace Oro\Bundle\UIBundle\Form\DataTransformer;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Class that can be used in forms for filtering sensible information by stripping tags
 *
 * @package Oro\Bundle\UIBundle\Form\DataTransformer
 */
class StripTagsTransformer implements DataTransformerInterface
{
    /**
     * @var HtmlTagHelper $htmlTagHelper
     */
    private $htmlTagHelper;

    /**
     * @param HtmlTagHelper $htmlTagHelper
     */
    public function __construct(HtmlTagHelper $htmlTagHelper)
    {
        $this->htmlTagHelper = $htmlTagHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $this->stripTags($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $this->stripTags($value);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function stripTags($value)
    {
        return $this->htmlTagHelper->stripTags($value);
    }
}
