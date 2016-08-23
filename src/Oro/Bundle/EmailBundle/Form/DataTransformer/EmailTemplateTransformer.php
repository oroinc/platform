<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;

class EmailTemplateTransformer implements DataTransformerInterface
{
    /** @var SanitizeHTMLTransformer */
    protected $transformer;

    /** @var bool Run sanitization transformer or not */
    /** @deprecated since 1.10 */
    private $sanitize;

    /**
     * @param SanitizeHTMLTransformer $transformer
     */
    public function __construct(SanitizeHTMLTransformer $transformer)
    {
        $this->transformer = $transformer;
        $this->sanitize = true;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if ($this->sanitize) {
            $value = $this->transformer->transform($value);
        }

        return $this->decodeTemplateVariables($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ($this->sanitize) {
            $value = $this->transformer->reverseTransform($value);
        }

        return $this->decodeTemplateVariables($value);
    }

    /**
     * Toggle sanitization transformer
     * @param bool $sanitize
     * @deprecated since 1.10
     */
    public function setSanitize($sanitize)
    {
        $this->sanitize = (bool) $sanitize;
    }

    /**
     * Decodes encoded brackets '{{' and '}}' and data inside them for further replacing with system/entity variables.
     *
     * @param string $value
     *
     * @return string
     */
    protected function decodeTemplateVariables($value)
    {
        return preg_replace_callback(
            '/%7B%7B.*%7D%7D/',
            function ($matches) {
                return urldecode(reset($matches));
            },
            $value
        );
    }
}
