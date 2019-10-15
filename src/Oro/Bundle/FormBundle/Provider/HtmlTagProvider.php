<?php

namespace Oro\Bundle\FormBundle\Provider;

/**
 * Used for getting white list elements and tags
 * Also provides HTMLPurifier modes
 */
class HtmlTagProvider
{
    private const HTML_ALLOWED_ELEMENTS = 'html_allowed_elements';
    private const HTML_PURIFIER_IFRAME_DOMAINS = 'html_purifier_iframe_domains';
    private const HTML_PURIFIER_URI_SCHEMES = 'html_purifier_uri_schemes';

    /** @var array */
    private $purifierConfig = [];

    /**
     * @param array $purifierConfig
     */
    public function __construct(array $purifierConfig)
    {
        $this->purifierConfig = $purifierConfig;
    }

    /**
     * Returns array of allowed elements
     *
     * @param string $scope
     * @return array
     */
    public function getAllowedElements(string $scope): array
    {
        $allowedElements = ['@[style|class]'];

        if (array_key_exists($scope, $this->purifierConfig)
            && array_key_exists(self::HTML_ALLOWED_ELEMENTS, $this->purifierConfig[$scope])) {
            foreach ($this->purifierConfig[$scope][self::HTML_ALLOWED_ELEMENTS] as $name => $data) {
                $allowedElement = $name;
                if (!empty($data['attributes'])) {
                    $allowedElement .= '[' . implode('|', $data['attributes']) . ']';
                }

                $allowedElements[] = $allowedElement;
            }
        }


        return $allowedElements;
    }

    /**
     * Returns string consisted from allowed tags
     *
     * @param string $scope
     * @return string
     */
    public function getAllowedTags(string $scope): string
    {
        $allowedTags = '';

        if (array_key_exists($scope, $this->purifierConfig)
            && array_key_exists(self::HTML_ALLOWED_ELEMENTS, $this->purifierConfig[$scope])) {
            foreach ($this->purifierConfig[$scope][self::HTML_ALLOWED_ELEMENTS] as $name => $data) {
                $allowedTag = '<' . $name . '>';
                if (!is_array($data) || !array_key_exists('hasClosingTag', $data) || $data['hasClosingTag']) {
                    $allowedTag .= '</' . $name . '>';
                }

                $allowedTags .= $allowedTag;
            }
        }

        return $allowedTags;
    }

    /**
     * @param string $scope
     * @return string
     */
    public function getIframeRegexp(string $scope): string
    {
        if (array_key_exists($scope, $this->purifierConfig)
            && array_key_exists(self::HTML_PURIFIER_IFRAME_DOMAINS, $this->purifierConfig[$scope])) {
            $iframeDomains = $this->purifierConfig[$scope][self::HTML_PURIFIER_IFRAME_DOMAINS];

            if (!$iframeDomains) {
                return '';
            }

            return sprintf('<^https?://(www.)?(%s)>', implode('|', $iframeDomains));
        }

        return '';
    }

    /**
     * @param string $scope
     * @return array
     */
    public function getUriSchemes(string $scope): array
    {
        $result = [];
        if (array_key_exists($scope, $this->purifierConfig)
            && array_key_exists(self::HTML_PURIFIER_URI_SCHEMES, $this->purifierConfig[$scope])) {
            foreach ($this->purifierConfig[$scope][self::HTML_PURIFIER_URI_SCHEMES] as $scheme) {
                $result[$scheme] = true;
            }
        }

        return $result;
    }
}
