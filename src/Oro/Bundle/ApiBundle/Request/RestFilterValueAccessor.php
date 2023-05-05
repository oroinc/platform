<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\FilterValueAccessor;
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
 *  * [key => [operator => value], ...]
 *  * [key => [operator name => value], ...]
 *  * [group => [key => value, ...], ...]
 * Example:
 * <code>
 *  [
 *      'filter' => [
 *          'name' => ['neq' => 'John']
 *      ],
 *      'sort' => 'name'
 *  ]
 * </code>
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class RestFilterValueAccessor extends FilterValueAccessor
{
    private Request $request;
    private string $operatorPattern;
    /** @var string[] [operator short name, ...] */
    private array $operators;
    /** @var array [operator name => operator short name or NULL, ...] */
    private array $operatorNameMap;
    /** @var array [operator short name => operator name, ...] */
    private array $operatorShortNameMap;

    public function __construct(Request $request, string $operatorPattern, array $operatorNameMap)
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
     * {@inheritDoc}
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->parseRequestBody();
        $this->parseQueryString();
    }

    /**
     * {@inheritDoc}
     */
    protected function normalizeOperator(?string $operator): string
    {
        $operator = parent::normalizeOperator($operator);

        if ('<>' === $operator) {
            $operator = '!=';
        }
        if (isset($this->operatorShortNameMap[$operator])) {
            $operator = $this->operatorShortNameMap[$operator];
        }

        return $operator;
    }

    private function addParsed(
        string $sourceKey,
        string $group,
        string $key,
        string $path,
        mixed $value,
        string $operator = '='
    ): void {
        if (null === $value) {
            $value = '';
        } elseif (!\is_string($value)) {
            throw new \UnexpectedValueException(sprintf(
                'Expected string value for the filter "%s", given "%s".',
                $sourceKey,
                get_debug_type($value)
            ));
        }

        $this->setParameter(
            $group,
            $key,
            FilterValue::createFromSource(
                $sourceKey,
                $path,
                $value,
                $this->normalizeOperator($operator)
            )
        );
    }

    private function parseQueryString(): void
    {
        $queryString = $this->request->server->get('QUERY_STRING');
        $queryString = RequestQueryStringNormalizer::normalizeQueryString($queryString);

        $queryString = '' === $queryString ? null : $queryString;

        if (empty($queryString)) {
            return;
        }

        $matchResult = preg_match_all(
            '/(?P<key>((?P<group>[\w\d\-\.]+)(?P<path>((\[[\w\d\-\.]*\])|(%5B[\w\d\-\.]*%5D))*)))'
            . '(?P<operator>' . $this->operatorPattern . ')'
            . '(?P<value>[^&]*)/',
            $queryString,
            $matches,
            PREG_SET_ORDER
        );

        if (false !== $matchResult) {
            foreach ($matches as $match) {
                $key = rawurldecode($match['key']);
                $group = rawurldecode($match['group']);
                $path = rawurldecode($match['path']);
                $operator = rawurldecode($match['operator']);

                // check if a filter is provided as "key[operator name]=value"
                if (str_ends_with($path, ']')) {
                    $pos = strrpos($path, '[');
                    if (false !== $pos) {
                        $lastElement = substr($path, $pos + 1, -1);
                        if (\array_key_exists($lastElement, $this->operatorNameMap)) {
                            $operator = $lastElement;
                            $key = substr($key, 0, -(\strlen($path) - $pos));
                            $path = substr($path, 0, $pos);
                        }
                    }
                }

                $normalizedKey = $key;
                if (empty($path)) {
                    $path = $key;
                } else {
                    $path = strtr($path, ['][' => ConfigUtil::PATH_DELIMITER, '[' => '', ']' => '']);
                    $normalizedKey = $group . '[' . $path . ']';
                }

                $this->addParsed($key, $group, $normalizedKey, $path, rawurldecode($match['value']), $operator);
            }
        }
    }

    private function parseRequestBody(): void
    {
        if (null === $this->request->request) {
            return;
        }

        $requestBody = $this->request->request->all();
        foreach ($requestBody as $group => $val) {
            if (\is_array($val)) {
                if ($this->isValueWithOperator($val)) {
                    $this->addParsed($group, $group, $group, $group, current($val), key($val));
                } elseif (ArrayUtil::isAssoc($val)) {
                    foreach ($val as $subKey => $subValue) {
                        $paramKey = $group . '[' . $subKey . ']';
                        if (\is_array($subValue) && $this->isValueWithOperator($subValue)) {
                            $this->addParsed(
                                $paramKey,
                                $group,
                                $paramKey,
                                $subKey,
                                current($subValue),
                                key($subValue)
                            );
                        } else {
                            $this->addParsed($paramKey, $group, $paramKey, $subKey, $subValue);
                        }
                    }
                } else {
                    $this->addParsed($group, $group, $group, $group, $val);
                }
            } else {
                $this->addParsed($group, $group, $group, $group, $val);
            }
        }
    }

    private function isValueWithOperator(array $value): bool
    {
        if (1 !== \count($value)) {
            return false;
        }

        $key = key($value);

        return
            \is_string($key)
            && (
                \in_array($key, $this->operators, true)
                || \array_key_exists($key, $this->operatorNameMap)
            );
    }
}
