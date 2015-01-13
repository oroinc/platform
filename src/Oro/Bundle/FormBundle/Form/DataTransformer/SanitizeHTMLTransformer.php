<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\FormBundle\Form\Converter\TagDefinitionConverter;

class SanitizeHTMLTransformer implements DataTransformerInterface
{
    /**
     * @var string|null
     */
    protected $allowedElements;

    /**
     * @param string|null $allowedElements
     */
    public function __construct($allowedElements = null)
    {
        $this->allowedElements = $allowedElements;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $this->sanitize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $this->sanitize($value);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function sanitize($value)
    {
        $config = \HTMLPurifier_Config::createDefault();
        $converter = new TagDefinitionConverter();

        if ($this->allowedElements) {
            $config->set('HTML.AllowedElements', $converter->getElements($this->allowedElements));
            $config->set('HTML.AllowedAttributes', $converter->getAttributes($this->allowedElements));
        }

        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($value);
    }
}
