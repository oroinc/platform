<?php

namespace Oro\Bundle\LocaleBundle\DQL;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

class DQLNameFormatter
{
    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var array */
    protected $namePartsMap = [
        'prefix'      => [
            'interface'          => 'Oro\\Bundle\\LocaleBundle\\Model\\NamePrefixInterface',
            'suggestedFieldName' => 'namePrefix'
        ],
        'first_name'  => [
            'interface'          => 'Oro\\Bundle\\LocaleBundle\\Model\\FirstNameInterface',
            'suggestedFieldName' => 'firstName'
        ],
        'middle_name' => [
            'interface'          => 'Oro\\Bundle\\LocaleBundle\\Model\\MiddleNameInterface',
            'suggestedFieldName' => 'middleName'
        ],
        'last_name'   => [
            'interface'          => 'Oro\\Bundle\\LocaleBundle\\Model\\LastNameInterface',
            'suggestedFieldName' => 'lastName'
        ],
        'suffix'      => [
            'interface'          => 'Oro\\Bundle\\LocaleBundle\\Model\\NameSuffixInterface',
            'suggestedFieldName' => 'nameSuffix'
        ],
    ];

    /**
     * @param NameFormatter $nameFormatter
     */
    public function __construct(NameFormatter $nameFormatter)
    {
        $this->nameFormatter = $nameFormatter;
    }

    /**
     * @param string      $alias     Alias in SELECT or JOIN statement
     * @param string      $className Entity FQCN
     * @param string|null $locale
     *
     * @return string
     */
    public function getFormattedNameDQL($alias, $className, $locale = null)
    {
        $nameParts  = array_fill_keys(array_keys($this->namePartsMap), null);
        $interfaces = class_implements($className);
        foreach ($this->namePartsMap as $part => $metadata) {
            if (in_array($metadata['interface'], $interfaces, true)) {
                $nameParts[$part] = QueryBuilderUtil::getField($alias, $metadata['suggestedFieldName']);
            }
        }

        return $this->buildExpression(
            $this->nameFormatter->getNameFormat(),
            $nameParts
        );
    }

    /**
     * @param string $nameFormat Localized name format string
     * @param array  $nameParts  Parts array
     *
     * @throws \LogicException
     * @return string
     */
    protected function buildExpression($nameFormat, array $nameParts)
    {
        $parts    = [];
        $prefix   = '';
        $suffixes = [];
        preg_match_all('/([^%]*)%(\w+)%([^%]*)/', $nameFormat, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[2] as $i => $key) {
                if ($i === 0 && !empty($matches[1][$i])) {
                    $prefix = $matches[1][$i];
                }
                $lowerCaseKey = strtolower($key);
                if (isset($nameParts[$lowerCaseKey])) {
                    $value = $nameParts[$lowerCaseKey];
                    if ($key !== $lowerCaseKey) {
                        $value = sprintf('UPPER(%s)', $nameParts[$lowerCaseKey]);
                    }

                    $parts[]    = $value;
                    $suffixes[] = $matches[3][$i];
                }
            }
        } else {
            throw new \LogicException('Unexpected name format given');
        }

        return $this->buildConcatExpression($parts, $prefix, $suffixes);
    }

    /**
     * @param string[] $parts
     * @param string   $prefix
     * @param string[] $separators
     *
     * @return string
     */
    protected function buildConcatExpression($parts, $prefix, $separators)
    {
        $count = count($parts);

        if ($count === 0) {
            return '';
        }

        if ($count === 1 && empty($prefix)) {
            return sprintf('CAST(%s as string)', reset($parts));
        }

        $items = [];
        if (!empty($prefix)) {
            // add prefix as first item
            $items[] = sprintf('\'%s\'', $prefix);
        }

        for ($i = 0; $i < $count; $i++) {
            // fix collation and type for CONCAT
            $item = sprintf('CAST(%s as string)', $parts[$i]);
            if (!empty($separators[$i])) {
                // add the separator
                $item = sprintf('CONCAT(%s, \'%s\')', $item, $separators[$i]);
            }
            // make sure we don't have null, because CONCAT will return null
            $items[] = sprintf('COALESCE(%s, \'\')', $item);
        }

        // join all as concat params
        return sprintf('TRIM(CONCAT(%s))', implode(', ', $items));
    }

    /**
     * Extract name parts paths for given entity based on interfaces implemented
     *
     * @param string $className     Entity FQCN
     * @param string $relationAlias Join alias for entity relation
     *
     * @return array
     */
    public function extractNamePartsPaths($className, $relationAlias)
    {
        $nameParts  = array_fill_keys(array_keys($this->namePartsMap), null);
        $interfaces = class_implements($className);

        foreach ($this->namePartsMap as $part => $metadata) {
            if (in_array($metadata['interface'], $interfaces, true)) {
                $format           = 'CASE WHEN %1$s.%2$s IS NOT NULL THEN %1$s.%2$s ELSE \'\' END';
                $nameParts[$part] = sprintf($format, $relationAlias, $metadata['suggestedFieldName']);
            }
        }

        return $nameParts;
    }

    /**
     * @param string|object $class
     * @return array
     */
    public function getSuggestedFieldNames($class)
    {
        $fields = [];
        foreach ($this->namePartsMap as $part => $metadata) {
            if (is_a($class, $metadata['interface'], true)) {
                $fields[$part] = $metadata['suggestedFieldName'];
            }
        }

        return $fields;
    }
}
