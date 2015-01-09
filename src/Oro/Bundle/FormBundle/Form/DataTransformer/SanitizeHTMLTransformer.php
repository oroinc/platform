<?php

namespace Oro\Bundle\FormBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class SanitizeHTMLTransformer implements DataTransformerInterface
{
    const DELIMITER = ',';

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
     * @return null|string
     */
    public function getAllowedElements()
    {
        if ($this->allowedElements) {
            return $this->prepareAllowedElements($this->allowedElements);
        }

        return $this->allowedElements;
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

        if ($this->allowedElements) {
            $config->set('HTML.Allowed', $this->getAllowedElements());
        }

        $purifier = new \HTMLPurifier($config);

        return $purifier->purify($value);
    }

    /**
     * Prepare list of allowable tags based on tinymce valid tags syntax.
     *
     * @param string $allowedElements
     * @return string
     */
    protected function prepareAllowedElements($allowedElements)
    {
    }
}
