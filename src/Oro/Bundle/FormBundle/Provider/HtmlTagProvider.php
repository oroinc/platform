<?php

namespace Oro\Bundle\FormBundle\Provider;

/**
 * Class HtmlTagProvider
 *
 * @package Oro\Bundle\FormBundle\Form\Provider
 */
class HtmlTagProvider
{
    /**
     * @var array
     */
    protected $elements = [
        [
            'name' => 'table',
            'attrs' => ['cellspacing', 'cellpadding', 'border', 'align', 'width'],
        ],
        [
            'name' => 'thead',
            'attrs' => ['align', 'valign'],
        ],
        [
            'name' => 'tbody',
            'attrs' => ['align', 'valign'],
        ],
        [
            'name' => 'tr',
            'attrs' => ['align', 'valign'],
        ],
        [
            'name' => 'td',
            'attrs' => ['align', 'valign', 'rowspan', 'colspan', 'bgcolor', 'nowrap', 'width', 'height'],
        ],
        [
            'name' => 'a',
            'attrs' => ['!href', 'target', 'title'],
        ],
        'dl',
        'dt',
        'div',
        'ul',
        'ol',
        'li',
        'em',
        'strong',
        'b',
        'p',
        [
            'name' => 'font',
            'attrs' => ['color'],
        ],
        'i',
        [
            'name' => 'br',
            'hasClosingTag' => false,
        ],
        'span',
        [
            'name' => 'img',
            'attrs' => ['src', 'width', 'height', 'alt'],
            'hasClosingTag' => false,
        ],
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
    ];

    /**
     * Returns array of allowed elements to use in TinyMCE plugin
     *
     * @return array
     */
    public function getAllowedElements()
    {
        $allowedElements = ['@[style|class]'];

        foreach ($this->elements as $element) {
            if (is_array($element)) {
                if (!array_key_exists('name', $element)) {
                    continue;
                }

                $allowedElement = $element['name'];
                if (array_key_exists('attrs', $element) && is_array($element['attrs'])) {
                    $allowedElement .= '[' . implode('|', $element['attrs']) . ']';
                }
            } else {
                $allowedElement = $element;
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

        foreach ($this->elements as $element) {
            if (is_array($element)) {
                if (!array_key_exists('name', $element)) {
                    continue;
                }

                $allowedTag = '<' . $element['name'] . '>';
                if (!array_key_exists('hasClosingTag', $element) || $element['hasClosingTag']) {
                    $allowedTag .= '</' . $element['name'] . '>';
                }
            } else {
                $allowedTag = '<' . $element . '>';
                $allowedTag .= '</' . $element . '>';
            }

            $allowedTags .= $allowedTag;
        }

        return $allowedTags;
    }
}
