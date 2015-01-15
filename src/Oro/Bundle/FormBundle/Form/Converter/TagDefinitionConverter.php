<?php

namespace Oro\Bundle\FormBundle\Form\Converter;

/**
 * Convert TinyMCE tags configuration to HTMLPurifier format
 */
class TagDefinitionConverter
{
    /**
     * @param string $allowedElements
     * @return string
     */
    public function getElements($allowedElements)
    {
        $tags = [];

        foreach (explode(',', $allowedElements) as $allowedElement) {
            if (strpos(trim($allowedElement), '@') !== false) {
                continue;
            }

            $tagsConfiguration = preg_replace('/\[.*\]/', '', $allowedElement);
            if (!$tagsConfiguration) {
                continue;
            }

            foreach (explode('/', $tagsConfiguration) as $tag) {
                if (empty($tag)) {
                    continue;
                }

                $tags[trim($tag)] = true;
            }
        }

        return array_keys($tags);
    }

    /**
     * @param string $allowedElements
     * @return string
     */
    public function getAttributes($allowedElements)
    {
        $attributes = [];

        foreach (explode(',', $allowedElements) as $allowedElement) {
            $attributesConfiguration = $this->parseAttributes($allowedElement);
            if (!$attributesConfiguration) {
                continue;
            }

            $tagsConfiguration = trim(preg_replace('/\[.*\]/', '', $allowedElement));
            if (!$tagsConfiguration) {
                continue;
            }
            $tags = ['*'];

            if (strpos($tagsConfiguration, '@') === false) {
                $tags = explode('/', $tagsConfiguration);
            }

            foreach ($tags as $tag) {
                foreach ($attributesConfiguration as $attribute) {
                    $attributes[sprintf('%s.%s', trim($tag), $attribute)] = true;
                }
            }
        }

        return array_keys($attributes);
    }

    /**
     * @param string $allowedElement
     * @return array
     */
    protected function parseAttributes($allowedElement)
    {
        $attributes = [];
        preg_match('/\[(.*?)\]/', $allowedElement, $attributesConfiguration);
        $attributesConfiguration = explode('|', end($attributesConfiguration));

        foreach ($attributesConfiguration as $attribute) {
            $attribute = trim(preg_replace('/(\w+)=.*/', '$1', $attribute), ' !');
            if (!$attribute) {
                continue;
            }

            $attributes[] = $attribute;
        }

        return $attributes;
    }
}
