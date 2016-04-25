<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class RestFilterValueAccessor implements FilterValueAccessorInterface
{
    /** @var Request */
    protected $request;

    /** @var FilterValue[] */
    protected $parameters;

    /** @var array */
    protected $groups;

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
     * {@inheritdoc}
     */
    public function getAll($group = null)
    {
        $this->ensureRequestParsed();

        if (empty($group)) {
            return $this->parameters;
        }

        return isset($this->groups[$group])
            ? $this->groups[$group]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, FilterValue $value = null)
    {
        $this->ensureRequestParsed();

        if (null !== $value) {
            $this->parameters[strtolower($key)] = $value;
        } else {
            unset($this->parameters[strtolower($key)]);
        }
    }

    /**
     * Makes sure the Request parsed
     */
    protected function ensureRequestParsed()
    {
        if (null === $this->parameters) {
            $this->parseRequest();
        }
    }

    /**
     * Extracts filters from the Request
     */
    protected function parseRequest()
    {
        $this->parameters = [];
        $this->groups     = [];

        // we should support filters that comes from request body and from URI part
        $requestData = $this->request->getContent() . '&' . $this->request->getQueryString();

        $matchResult = preg_match_all(
            '/(?P<key>((?P<group>[\w\d-\.]+)(?P<path>((\[[\w\d-\.]+\])|(%5B[\w\d-\.]+%5D))*)))'
            . '(?P<operator>(<|>|%3C|%3E)?=|<>|%3C%3E|(<|>|%3C|%3E))'
            . '(?P<value>[^&]+)/',
            $requestData,
            $matches,
            PREG_SET_ORDER
        );

        if (false !== $matchResult) {
            foreach ($matches as $match) {
                $key   = strtolower(rawurldecode($match['key']));
                $group = strtolower(rawurldecode($match['group']));
                $path  = strtolower(rawurldecode($match['path']));
                $path  = !empty($path)
                    ? strtr($path, ['][' => ConfigUtil::PATH_DELIMITER, '[' => '', ']' => ''])
                    : $key;
                $value = new FilterValue(
                    $path,
                    rawurldecode($match['value']),
                    rawurldecode(strtolower($match['operator']))
                );

                $this->parameters[$key]     = $value;
                $this->groups[$group][$key] = $value;
            }
        }
    }
}
