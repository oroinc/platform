<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessorInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extracts values of filters from REST API HTTP request.
 * Filters can be sent in the query string or the request body.
 * If a filter exists in both the query string and the request body,
 * the filter from the query string will override the filter from the request body.
 *
 * Filter syntax for the query string:
 * * key=value, where "=" is an operator; see $this->operators to find a list of supported operators
 * * key[operator name]=value, where "operator name" can be "eq", "neq", etc.; see $this->operatorNameMap
 *                             to find a map between operators and their names
 * Examples:
 * * /api/users?filter[name]!=John
 * * /api/users?filter[name][neq]=John
 * * /api/users?page[number]=10&sort=name
 *
 * Filter syntax for the request body:
 *  * [key => value, ...]
 *  * [key => [operator, value], ...]
 *  * [key => [operator name, value], ...]
 *  * [group => [key => value, ...], ...]
 * Example:
 * <code>
 *  [
 *      'filter' => [
 *          'name' => ['neq', 'John']
 *      ],
 *      'sort' => 'name'
 *  ]
 * </code>
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RestFilterValueAccessor implements FilterValueAccessorInterface
{
    /** @var Request */
    private $request;

    /** @var string */
    private $operatorPattern;

    /** @var string[] [operator short name, ...] */
    private $operators;

    /** @var array [operator name => operator short name or NULL, ...] */
    private $operatorNameMap;

    /** @var array [operator short name => operator name, ...] */
    private $operatorShortNameMap;

    /** @var FilterValue[] */
    private $parameters;

    /** @var array */
    private $groups;

    /** @var string|null */
    private $defaultGroupName;

    /**
     * @param Request $request
     * @param string  $operatorPattern
     * @param array   $operatorNameMap
     */
    public function __construct(Request $request, $operatorPattern, array $operatorNameMap)
    {
        $this->request = $request;
        $this->operatorPattern = $operatorPattern;
        $this->operatorNameMap = $operatorNameMap;
        $this->operators = [];
        $this->operatorShortNameMap = [];
        foreach ($operatorNameMap as $name => $shortName) {
            if ($shortName && !\array_key_exists($shortName, $this->operatorShortNameMap)) {
                $this->operators[] = $shortName;
                $this->operatorShortNameMap[$shortName] = $name;
            }
        }
        // "<>" is an alias for "!="
        $this->operators[] = '<>';
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        $this->ensureRequestParsed();

        if (isset($this->parameters[$key])) {
            return true;
        }

        $result = false;
        if ($this->defaultGroupName && isset($this->groups[$this->defaultGroupName])) {
            /** @var FilterValue $value */
            foreach ($this->groups[$this->defaultGroupName] as $value) {
                if ($value->getPath() === $key) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $this->ensureRequestParsed();

        if (isset($this->parameters[$key])) {
            return $this->parameters[$key];
        }

        $result = null;
        if ($this->defaultGroupName && isset($this->groups[$this->defaultGroupName])) {
            /** @var FilterValue $value */
            foreach ($this->groups[$this->defaultGroupName] as $value) {
                if ($value->getPath() === $key) {
                    $result = $value;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup($group)
    {
        $this->ensureRequestParsed();

        if (!isset($this->groups[$group])) {
            return [];
        }

        return $this->groups[$group];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultGroupName()
    {
        return $this->defaultGroupName;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultGroupName($group)
    {
        $this->defaultGroupName = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll()
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

        $group = $this->extractGroup($key);
        if (null !== $value) {
            $value->setOperator($this->normalizeOperator($value->getOperator()));
            $this->parameters[$key] = $value;
            $this->groups[$group][$key] = $value;
        } else {
            unset($this->parameters[$key], $this->groups[$group][$key]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        $this->ensureRequestParsed();

        $group = $this->extractGroup($key);

        unset($this->parameters[$key], $this->groups[$group][$key]);
    }

    /**
     * Makes sure the Request parsed
     */
    private function ensureRequestParsed()
    {
        if (null === $this->parameters) {
            $this->parseRequest();
        }
    }

    /**
     * Extracts filters from the Request
     */
    private function parseRequest()
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
    private function addParsed($group, $key, $path, $value, $operator)
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

    private function parseQueryString()
    {
        $queryString = $this->request->getQueryString();
        if (empty($queryString)) {
            return;
        }

        $matchResult = \preg_match_all(
            '/(?P<key>((?P<group>[\w\d-\.]+)(?P<path>((\[[\w\d-\.]*\])|(%5B[\w\d-\.]*%5D))*)))'
            . '(?P<operator>' . $this->operatorPattern . ')'
            . '(?P<value>[^&]+)/',
            $queryString,
            $matches,
            PREG_SET_ORDER
        );

        if (false !== $matchResult) {
            foreach ($matches as $match) {
                $key = \rawurldecode($match['key']);
                $group = \rawurldecode($match['group']);
                $path = \rawurldecode($match['path']);
                $operator = \rawurldecode($match['operator']);

                // check if a filter is provided as "key[operator name]=value"
                if (\substr($path, -1) === ']') {
                    $pos = \strrpos($path, '[');
                    if (false !== $pos) {
                        $lastElement = \substr($path, $pos + 1, -1);
                        if (\array_key_exists($lastElement, $this->operatorNameMap)) {
                            $operator = $lastElement;
                            $key = \substr($key, 0, -(\strlen($path) - $pos));
                            $path = \substr($path, 0, $pos);
                        }
                    }
                }

                $normalizedKey = $key;
                if (empty($path)) {
                    $path = $key;
                } else {
                    $path = \strtr($path, ['][' => ConfigUtil::PATH_DELIMITER, '[' => '', ']' => '']);
                    $normalizedKey = $group . '[' . $path . ']';
                }

                $this->addParsed($group, $normalizedKey, $path, \rawurldecode($match['value']), $operator)
                    ->setSourceKey($key);
            }
        }
    }

    private function parseRequestBody()
    {
        if (null === $this->request->request) {
            return;
        }

        $requestBody = $this->request->request->all();
        foreach ($requestBody as $group => $val) {
            if (\is_array($val)) {
                if ($this->isValueWithOperator($val)) {
                    $this->addParsed($group, $group, $group, \current($val), \key($val))->setSourceKey($group);
                } elseif (!ArrayUtil::isAssoc($val)) {
                    $this->addParsed($group, $group, $group, $val, '=')->setSourceKey($group);
                } else {
                    foreach ($val as $subKey => $subValue) {
                        $paramKey = $group . '[' . $subKey . ']';
                        if (\is_array($subValue) && $this->isValueWithOperator($subValue)) {
                            $this->addParsed($group, $paramKey, $subKey, \current($subValue), \key($subValue))
                                ->setSourceKey($paramKey);
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
    private function isValueWithOperator(array $value)
    {
        if (1 !== \count($value)) {
            return false;
        }

        $key = \key($value);

        return
            \is_string($key)
            && (
                \in_array($key, $this->operators, true)
                || \array_key_exists($key, $this->operatorNameMap)
            );
    }

    /**
     * @param string $operator
     *
     * @return string
     */
    private function normalizeOperator($operator)
    {
        if ('<>' === $operator) {
            $operator = '!=';
        }
        if (isset($this->operatorShortNameMap[$operator])) {
            $operator = $this->operatorShortNameMap[$operator];
        }

        return $operator;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    private function extractGroup($key)
    {
        $delimPos = \strpos($key, '[');

        return false !== $delimPos && \substr($key, -1) === ']'
            ? \substr($key, 0, $delimPos)
            : $key;
    }
}
