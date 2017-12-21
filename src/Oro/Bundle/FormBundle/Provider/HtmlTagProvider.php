<?php

namespace Oro\Bundle\FormBundle\Provider;

class HtmlTagProvider
{
    /** @var array */
    protected $elements = [];

    /**
     * @param array $elements
     */
    public function __construct(array $elements)
    {
        $this->elements = $elements;
    }

    /**
     * Returns array of allowed elements to use in TinyMCE plugin
     *
     * @return array
     */
    public function getAllowedElements()
    {
        $allowedElements = ['@[style|class]'];

        foreach ($this->elements as $name => $data) {
            $allowedElement = $name;
            if (!empty($data['attributes'])) {
                $allowedElement .= '[' . implode('|', $data['attributes']) . ']';
            }

            $allowedElements[] = $allowedElement;
        }

        return $allowedElements;
    }

    /**
     * Returns string consisted from allowed tags
     *
     * @return string
     */
    public function getAllowedTags()
    {
        $allowedTags = '';

        foreach ($this->elements as $name => $data) {
            $allowedTag = '<' . $name . '>';
            if (!array_key_exists('hasClosingTag', $data) || $data['hasClosingTag']) {
                $allowedTag .= '</' . $name . '>';
            }

            $allowedTags .= $allowedTag;
        }

        return $allowedTags;
    }
}
