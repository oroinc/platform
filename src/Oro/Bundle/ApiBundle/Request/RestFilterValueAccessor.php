<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;

class RestFilterValueAccessor implements FilterValueAccessorInterface
{
    /** @var Request */
    protected $request;

    /** @var FilterValue[] */
    protected $parameters;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        $this->ensureRequestParsed();

        return isset($this->parameters[strtolower($key)]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $this->ensureRequestParsed();

        $key = strtolower($key);

        return isset($this->parameters[$key])
            ? $this->parameters[$key]
            : null;
    }

    /**
     * Makes sure the Request parsed
     */
    protected function ensureRequestParsed()
    {
        if (null === $this->parameters) {
            $this->parameters = $this->parseRequest();
        }
    }

    /**
     * Extracts filters from the Request
     *
     * @return array
     */
    protected function parseRequest()
    {
        $parameters = [];

        $matchResult = preg_match_all(
            '/(?P<name>([\w\d-\.]+(%5B[\w\d-\.]+%5D)*))'
            . '(?P<operator>(<|>|%3C|%3E)?=|<>|%3C%3E|(<|>|%3C|%3E))'
            . '(?P<value>[^&]+)/',
            $this->request->getQueryString(),
            $matches,
            PREG_SET_ORDER
        );
        if (false === $matchResult) {
            return $parameters;
        }

        foreach ($matches as $match) {
            $parameters[strtolower(rawurldecode($match['name']))] = new FilterValue(
                rawurldecode($match['value']),
                rawurldecode(strtolower($match['operator']))
            );
        }

        return $parameters;
    }
}
