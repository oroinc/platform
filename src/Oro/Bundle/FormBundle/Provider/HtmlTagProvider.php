<?php

namespace Oro\Bundle\FormBundle\Provider;

/**
 * Used for getting white list elements and tags
 * Also provides HTMLPurifier modes
 */
class HtmlTagProvider
{
    private const EXTEND_KEY = 'extends';
    private const ALLOWED_HTML_ELEMENTS = 'allowed_html_elements';
    private const ALLOWED_IFRAME_DOMAINS = 'allowed_iframe_domains';
    private const ALLOWED_URI_SCHEMES = 'allowed_uri_schemes';
    private const ALLOWED_REL = 'allowed_rel';

    /** @var array */
    private $purifierConfig = [];

    public function __construct(array $purifierConfig)
    {
        $this->purifierConfig = $purifierConfig;
    }

    /**
     * Returns the list of supported scopes.
     */
    public function getScopes(): array
    {
        return array_keys($this->purifierConfig);
    }

    /**
     * Returns array of allowed elements
     */
    public function getAllowedElements(string $scope): array
    {
        $allowedElements = ['@[id|style|class]'];
        foreach ($this->getPurifierConfigByKey($scope, self::ALLOWED_HTML_ELEMENTS) as $name => $data) {
            $allowedElement = $name;
            if (!empty($data['attributes'])) {
                $allowedElement .= '[' . implode('|', $data['attributes']) . ']';
            }

            $allowedElements[] = $allowedElement;
        }

        return $allowedElements;
    }

    /**
     * Returns an array of allowed rel attribute values.
     */
    public function getAllowedRel(string $scope): array
    {
        return $this->getPurifierConfigByKey($scope, self::ALLOWED_REL);
    }

    /**
     * Returns array of allowed iframe domains
     */
    public function getAllowedIframeDomains(string $scope): array
    {
        return $this->getPurifierConfigByKey($scope, self::ALLOWED_IFRAME_DOMAINS);
    }

    /**
     * Returns string consisted from allowed tags
     */
    public function getAllowedTags(string $scope): string
    {
        $allowedTags = '';
        foreach ($this->getPurifierConfigByKey($scope, self::ALLOWED_HTML_ELEMENTS) as $name => $data) {
            $allowedTag = '<' . $name . '>';
            if (!is_array($data) || !array_key_exists('hasClosingTag', $data) || $data['hasClosingTag']) {
                $allowedTag .= '</' . $name . '>';
            }

            $allowedTags .= $allowedTag;
        }

        return $allowedTags;
    }

    public function getIframeRegexp(string $scope): string
    {
        $iframeDomains = $this->getPurifierConfigByKey($scope, self::ALLOWED_IFRAME_DOMAINS);
        if (!$iframeDomains) {
            return '';
        }

        return sprintf('<^https?://(www.)?(%s)>', implode('|', $iframeDomains));
    }

    public function getUriSchemes(string $scope): array
    {
        $result = [];
        foreach ($this->getPurifierConfigByKey($scope, self::ALLOWED_URI_SCHEMES) as $scheme) {
            $result[$scheme] = true;
        }

        return $result;
    }

    /**
     * @param string $scope
     * @param string $key
     * @return array
     */
    private function getPurifierConfigByKey($scope, $key): array
    {
        $purifierConfig = [
            self::ALLOWED_HTML_ELEMENTS => [],
            self::ALLOWED_IFRAME_DOMAINS => [],
            self::ALLOWED_URI_SCHEMES => [],
            self::ALLOWED_REL => []
        ];

        if (array_key_exists($scope, $this->purifierConfig)) {
            $purifierConfigByKey = $purifierConfig[$key];

            if (array_key_exists(self::EXTEND_KEY, $this->purifierConfig[$scope])) {
                $purifierConfigByKey = array_merge_recursive(
                    $purifierConfigByKey,
                    $this->getPurifierConfigByKey($this->purifierConfig[$scope][self::EXTEND_KEY], $key)
                );
            }

            if (array_key_exists($key, $this->purifierConfig[$scope])) {
                $purifierConfigByKey = array_merge_recursive($purifierConfigByKey, $this->purifierConfig[$scope][$key]);
            }

            $purifierConfig[$key] = $purifierConfigByKey;
        }

        return $purifierConfig[$key];
    }
}
