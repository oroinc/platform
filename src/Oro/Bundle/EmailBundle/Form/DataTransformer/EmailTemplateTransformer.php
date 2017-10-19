<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class EmailTemplateTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        $value = $this->decodeTemplateVariables($value);

        return $this->decodeHtmlSpecialCharsFromTwigTags($value);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $value = $this->decodeTemplateVariables($value);

        return $this->decodeHtmlSpecialCharsFromTwigTags($value);
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

    /**
     * Decodes all html special chars in the twig tags '{% %}' and '{{ }}' for example '{% if variable &gt; 1 %}'
     *
     * @param string $value
     *
     * @return string
     */
    private function decodeHtmlSpecialCharsFromTwigTags($value)
    {
        return preg_replace_callback(
            '/({{|{%)[^}]+(%}|}})/',
            function ($matches) {
                return htmlspecialchars_decode(reset($matches));
            },
            $value
        );
    }
}
