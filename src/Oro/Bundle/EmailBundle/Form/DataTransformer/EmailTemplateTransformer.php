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
     * Replaces escaped brackets '{{' and '}}' for further replacing these values with system/entity variables.
     *
     * {@inheritdoc}
     */
    public function transform($value)
    {
        $value = $this->transformer->transform($value);

        return preg_replace('/(%7B%7B(.*?)%7D%7D)/', '{{$2}}', $value);
    }

    /**
     * Replaces escaped brackets '{{' and '}}' for further replacing these values with system/entity variables.
     *
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $value = $this->transformer->reverseTransform($value);

        return preg_replace('/(%7B%7B(.*?)%7D%7D)/', '{{$2}}', $value);
    }
}
