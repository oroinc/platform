<?php

namespace Oro\Bundle\FormBundle\Provider;

/**
 * Used for getting white list elements and tags
 * Also provides HTMLPurifier modes
 */
class HtmlTagProvider
{
    const HTML_PURIFIER_MODE_STRICT = 'strict';
    const HTML_PURIFIER_MODE_EXTENDED = 'extended';
    const HTML_PURIFIER_MODE_DISABLED = 'disabled';

    /** elements forbidden in strict mode */
    const STRICT_ELEMENTS = ['iframe', 'style'];

    /** @var array */
    protected $elements = [];

    /** @var string */
    private $htmlPurifierMode;

    /** @var array */
    private $iframeDomains;

    /** @var array */
    private $uriSchemes;

    /**
     * @param array $elements
     * @param string $htmlPurifierMode
     * @param array $iframeDomains
     * @param array $uriSchemes
     */
    public function __construct(
        array $elements,
        $htmlPurifierMode = self::HTML_PURIFIER_MODE_STRICT,
        $iframeDomains = [],
        $uriSchemes = []
    ) {
        $this->elements = $elements;
        $this->htmlPurifierMode = $htmlPurifierMode;
        $this->iframeDomains = $iframeDomains;
        $this->uriSchemes = $uriSchemes;

        $this->filterElementsForStrictMode($this->elements);
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

    private function filterElementsForStrictMode(array &$allowedElements)
    {
        if ($this->htmlPurifierMode === self::HTML_PURIFIER_MODE_STRICT) {
            $allowedElements = array_filter(
                $allowedElements,
                function ($allowedElement) {
                    return !in_array($allowedElement, self::STRICT_ELEMENTS);
                },
                ARRAY_FILTER_USE_KEY
            );
        }
    }

    /**
     * @return bool
     */
    public function isPurificationNeeded()
    {
        return $this->htmlPurifierMode !== self::HTML_PURIFIER_MODE_DISABLED;
    }

    /**
     * @return bool
     */
    public function isExtendedPurification()
    {
        return $this->htmlPurifierMode === self::HTML_PURIFIER_MODE_EXTENDED;
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
