<?php

namespace Oro\Bundle\CronBundle\Filter;

use Oro\Bundle\EntityBundle\ORM\QueryUtils;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class CommandWithArgsFilter extends StringFilter
{
    const REGEX_STRING = '([^\s]+?)(?:\s|(?<!\\\\)"|(?<!\\\\)\'|$)';
    const REGEX_QUOTED_STRING = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\')';

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $type = $data['type'];

        $values = is_array($data['value']) ? $data['value'] : [$data['value']];
        foreach ($values as $value) {
            $parameterName = $ds->generateParameterName($this->getName());
            $this->applyFilterToClause(
                $ds,
                $this->buildComparisonExpr(
                    $ds,
                    $type,
                    $this->getFieldExpr($type),
                    $parameterName
                )
            );
            if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
                $ds->setParameter($parameterName, $value);
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseValue($comparisonType, $value)
    {
        switch ($comparisonType) {
            case TextFilterType::TYPE_CONTAINS:
            case TextFilterType::TYPE_NOT_CONTAINS:
                // a value for CONTAINS and NOT CONTAINS may contains several parts
                return $this->getValueParts($comparisonType, $value);
            default:
                return parent::parseValue($comparisonType, $value);
        }
    }

    /**
     * Returns an DQL expression the filter should be applied to
     *
     * @param int $comparisonType
     *
     * @return string
     */
    protected function getFieldExpr($comparisonType)
    {
        $dataName = array_map(
            'trim',
            preg_split('/,/', $this->get(FilterUtility::DATA_NAME_KEY), -1, PREG_SPLIT_NO_EMPTY)
        );
        switch ($comparisonType) {
            case TextFilterType::TYPE_CONTAINS:
            case TextFilterType::TYPE_NOT_CONTAINS:
                // CONTAINS and NOT CONTAINS should search in all field
                return QueryUtils::buildConcatExpr($dataName);
            default:
                // other comparisons should work only for the first column
                return reset($dataName);
        }
    }

    /**
     * @param int    $comparisonType
     * @param string $value
     *
     * @return array
     */
    protected function getValueParts($comparisonType, $value)
    {
        try {
            $tokens = $this->tokenize($value);
        } catch (\InvalidArgumentException $e) {
            // use simplifier tokenizer for an invalid value
            $tokens = preg_split('/ /', $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        return array_map(
            function ($val) use ($comparisonType) {
                return parent::parseValue($comparisonType, $val);
            },
            $tokens
        );
    }

    /**
     * @param string $str The input string to tokenize
     *
     * @return string[] An array of tokens
     *
     * @throws \InvalidArgumentException When unable to parse the input string
     */
    protected function tokenize($str)
    {
        $tokens = [];
        $length = strlen($str);
        $i      = 0;
        while ($i < $length) {
            if (preg_match('/\s+/A', $str, $match, null, $i)) {
            } elseif (preg_match('/([^="\'\s]+?)(=?)(' . self::REGEX_QUOTED_STRING . '+)/A', $str, $match, null, $i)) {
                $tokens[] = $match[1] . $match[2] . $this->encloseString(substr($match[3], 1, strlen($match[3]) - 2));
            } elseif (preg_match('/' . self::REGEX_QUOTED_STRING . '/A', $str, $match, null, $i)) {
                $tokens[] = $this->encloseString(substr($match[0], 1, strlen($match[0]) - 2));
            } elseif (preg_match('/' . self::REGEX_STRING . '/A', $str, $match, null, $i)) {
                $tokens[] = stripcslashes($match[1]);
            } else {
                // should never happen
                throw new \InvalidArgumentException(
                    sprintf('Unable to parse input near "... %s ..."', substr($str, $i, 10))
                );
            }

            $i += strlen($match[0]);
        }

        return $tokens;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    protected function encloseString($str)
    {
        return '\\\\"' . str_replace('\\', '\\\\', $str) . '\\\\"';
    }
}
