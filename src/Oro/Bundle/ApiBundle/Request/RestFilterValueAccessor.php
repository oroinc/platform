<?php

namespace Oro\Bundle\ApiBundle\Request;

use Symfony\Component\HttpFoundation\Request;

use Oro\Component\PhpUtils\ArrayUtil;
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

        return isset($this->parameters[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $this->ensureRequestParsed();

        return isset($this->parameters[$key])
            ? $this->parameters[$key]
            : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup($group)
    {
        $this->ensureRequestParsed();

        return isset($this->groups[$group])
            ? $this->groups[$group]
            : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($group = null)
    {
        $this->ensureRequestParsed();

        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, FilterValue $value = null)
    {
        $this->ensureRequestParsed();

        $delimPos = strpos($key, '[');
        $group = false !== $delimPos && substr($key, -1) === ']'
            ? substr($key, 0, $delimPos)
            : $key;
        if (null !== $value) {
            $value->setOperator($this->normalizeOperator($value->getOperator()));
            $this->parameters[$key] = $value;
            $this->groups[$group][$key] = $value;
        } else {
            unset($this->parameters[$key]);
            unset($this->groups[$group][$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $this->ensureRequestParsed();

        $delimPos = strpos($key, '[');
        $group = false !== $delimPos && substr($key, -1) === ']'
            ? substr($key, 0, $delimPos)
            : $key;

        unset($this->parameters[$key]);
        unset($this->groups[$group][$key]);
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
        $this->groups = [];

        $this->parseRequestBody();
        $this->parseQueryString();
    }

    /**
     * @param string $group
     * @param string $key
     * @param string $path
     * @param mixed  $value
     * @param string $operator
     *
     * @return FilterValue
     */
    protected function addParsed($group, $key, $path, $value, $operator)
    {
        $filterValue = new FilterValue(
            $path,
            $value,
            $this->normalizeOperator($operator)
        );
        $this->parameters[$key] = $filterValue;
        $this->groups[$group][$key] = $filterValue;

        return $filterValue;
    }

    protected function parseQueryString()
    {
        $queryString = $this->request->getQueryString();
        if (empty($queryString)) {
            return;
        }

        $matchResult = preg_match_all(
            '/(?P<key>((?P<group>[\w\d-\.]+)(?P<path>((\[[\w\d-\.]*\])|(%5B[\w\d-\.]*%5D))*)))'
            . '(?P<operator>(' . $this->getOperatorPattern() . ')'
            . '(?P<value>[^&]+)/',
            $queryString,
            $matches,
            PREG_SET_ORDER
        );

        if (false !== $matchResult) {
            foreach ($matches as $match) {
                $key = rawurldecode($match['key']);
                $group = rawurldecode($match['group']);
                $path = rawurldecode($match['path']);
                $normalizedKey = $key;
                if (empty($path)) {
                    $path = $key;
                } else {
                    $path = strtr($path, ['][' => ConfigUtil::PATH_DELIMITER, '[' => '', ']' => '']);
                    $normalizedKey = $group . '[' . $path . ']';
                }

                $this->addParsed(
                    $group,
                    $normalizedKey,
                    $path,
                    rawurldecode($match['value']),
                    rawurldecode($match['operator'])
                )->setSourceKey($key);
            }
        }
    }

    protected function parseRequestBody()
    {
        if (null === $this->request->request) {
            return;
        }

        $requestBody = $this->request->request->all();
        foreach ($requestBody as $group => $val) {
            if (is_array($val)) {
                if ($this->isValueWithOperator($val)) {
                    $this->addParsed($group, $group, $group, current($val), key($val))->setSourceKey($group);
                } elseif (!ArrayUtil::isAssoc($val)) {
                    $this->addParsed($group, $group, $group, $val, '=')->setSourceKey($group);
                } else {
                    foreach ($val as $subKey => $subValue) {
                        $paramKey = $group . '[' . $subKey . ']';
                        if (is_array($subValue) && $this->isValueWithOperator($subValue)) {
                            $this->addParsed(
                                $group,
                                $paramKey,
                                $subKey,
                                current($subValue),
                                key($subValue)
                            )->setSourceKey($paramKey);
                        } else {
                            $this->addParsed($group, $paramKey, $subKey, $subValue, '=')->setSourceKey($paramKey);
                        }
                    }
                }
            } else {
                $this->addParsed($group, $group, $group, $val, '=')->setSourceKey($group);
            }
        }
    }

    /**
     * @param array $value
     *
     * @return bool
     */
    protected function isValueWithOperator(array $value)
    {
        if (1 !== count($value)) {
            return false;
        }

        $key = key($value);

        return
            is_string($key)
            && in_array($key, $this->getOperators(), true);
    }

    /**
     * @param string $operator
     *
     * @return string
     */
    protected function normalizeOperator($operator)
    {
        if ('<>' === $operator) {
            return '!=';
        }

        return $operator;
    }

    /**
     * @return string[]
     */
    protected function getOperators()
    {
        return ['=', '!=', '>', '<', '>=', '<=', '<>'];
    }

    /**
     * @return string
     */
    protected function getOperatorPattern()
    {
        return '!|<|>|%21|%3C|%3E)?=|<>|%3C%3E|(<|>|%3C|%3E)';
    }
}
