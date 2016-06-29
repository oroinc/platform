<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\SanitizeHTMLTransformer;

class EmailTemplateTransformer implements DataTransformerInterface
{
    /** @var SanitizeHTMLTransformer */
    protected $transformer;

    /**
     * @param SanitizeHTMLTransformer $transformer
     */
    public function __construct(SanitizeHTMLTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        $value = $this->transformer->transform($value);

        return $this->decodeTemplateVariables($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $value = $this->transformer->reverseTransform($value);

        return $this->decodeTemplateVariables($value);
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
