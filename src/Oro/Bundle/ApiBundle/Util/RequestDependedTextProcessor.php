<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Request\RequestType;

class RequestDependedTextProcessor
{
    const START_REQUEST_TAG = '{@request:';
    const END_REQUEST_TAG   = '{@/request}';

    /** @var RequestExpressionMatcher */
    private $matcher;

    /**
     * @param RequestExpressionMatcher $matcher
     */
    public function __construct(RequestExpressionMatcher $matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     * @param string      $text
     * @param RequestType $requestType
     *
     * @return string
     */
    public function process($text, RequestType $requestType)
    {
        $offset = 0;
        $startLength = strlen(self::START_REQUEST_TAG);
        $endLength = strlen(self::END_REQUEST_TAG);
        while (false !== ($startOpenPos = strpos($text, self::START_REQUEST_TAG, $offset))) {
            $startClosePos = strpos($text, '}', $startOpenPos + $startLength);
            if (false === $startClosePos) {
                break;
            }
            $expression = substr(
                $text,
                $startOpenPos + $startLength,
                $startClosePos - $startOpenPos - $startLength
            );
            if (!$expression) {
                break;
            }
            $endClosePos = strpos($text, self::END_REQUEST_TAG, $startClosePos + 1);
            if (false === $endClosePos) {
                break;
            }

            $body = '';
            if ($this->matcher->matchValue($expression, $requestType)) {
                $body = substr($text, $startClosePos + 1, $endClosePos - $startClosePos - 1);
            }

            $text = substr_replace($text, $body, $startOpenPos, ($endClosePos + $endLength) - $startOpenPos);
        }

        return $text;
    }
}
