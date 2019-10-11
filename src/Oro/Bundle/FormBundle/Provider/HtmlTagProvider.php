<?php

namespace Oro\Bundle\FormBundle\Provider;

/**
 * Used for getting white list elements and tags
 * Also provides HTMLPurifier modes
 */
class HtmlTagProvider
{
    /** @var array */
    protected $elements = [];

    /** @var array */
    private $iframeDomains;

    /** @var array */
    private $uriSchemes;

    /**
     * @param array $elements
     * @param array $iframeDomains
     * @param array $uriSchemes
     */
    public function __construct(
        array $elements,
        $iframeDomains = [],
        $uriSchemes = []
    ) {
        $this->elements = $elements;
        $this->iframeDomains = $iframeDomains;
        $this->uriSchemes = $uriSchemes;
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

    /**
     * @return string
     */
    public function getIframeRegexp()
    {
        if (!$this->iframeDomains) {
            return '';
        }

        return sprintf('<^https?://(www.)?(%s)>', implode('|', $this->iframeDomains));
    }

    /**
     * @return array
     */
    public function getUriSchemes()
    {
        $result = [];

        foreach ($this->uriSchemes as $scheme) {
            $result[$scheme] = true;
        }

        return $result;
    }
}
