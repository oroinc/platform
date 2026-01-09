<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * Sanitizes passed value using html purifier with configured attributes which is enabled
 */
class SanitizeHTMLTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    private $scope;

    /**
     * @var HtmlTagHelper
     */
    private $htmlTagHelper;

    /**
     * @param HtmlTagHelper $htmlTagHelper
     * @param string $scope
     */
    public function __construct(HtmlTagHelper $htmlTagHelper, $scope = 'default')
    {
        $this->htmlTagHelper = $htmlTagHelper;
        $this->scope = $scope;
    }

    #[\Override]
    public function transform($value): mixed
    {
        return $this->htmlTagHelper->sanitize($value, $this->scope);
    }

    #[\Override]
    public function reverseTransform($value): mixed
    {
        return $this->htmlTagHelper->sanitize($value, $this->scope);
    }
}
