<?php

namespace Oro\Bundle\LocaleBundle\DQL;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;

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
     * @param string $alias     Alias in SELECT or JOIN statement
     * @param string $className Entity FQCN
     *
     * @return string
     */
    public function getFormattedNameDQL($alias, $className)
    {
        $nameFormat = $this->nameFormatter->getNameFormat();
        $nameParts  = $this->extractNamePartsPaths($className, $alias);

        return $this->buildExpression($nameFormat, $nameParts);
    }

    /**
     * @param string $nameFormat Localized name format string
     * @param array  $nameParts  Parts array
     *
     * @throws \LogicException
     * @return string
     */
    private function buildExpression($nameFormat, array $nameParts)
    {
        $parts = $stack = [];
        preg_match_all('/%(\w+)%([^%]*)/', $nameFormat, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[0] as $idx => $match) {
                $key              = $matches[1][$idx];
                $prependSeparator = isset($matches[2], $matches[2][$idx]) ? $matches[2][$idx] : '';
                $lowerCaseKey     = strtolower($key);
                if (isset($nameParts[$lowerCaseKey])) {
                    $value = $nameParts[$lowerCaseKey];
                    if ($key !== $lowerCaseKey) {
                        $value = sprintf('UPPER(%s)', $nameParts[$lowerCaseKey]);
                    }

                    $parts[] = $value;
                    if (strlen($prependSeparator) !== 0) {
                        $parts[] = sprintf("'%s'", $prependSeparator);
                    }
                }
            }
        } else {
            throw new \LogicException('Unexpected name format given');
        }

        for ($i = count($parts) - 1; $i >= 0; $i--) {
            if (count($stack) === 0) {
                array_push($stack, $parts[$i]);
            } else {
                array_push($stack, sprintf('CONCAT(%s, %s)', $parts[$i], array_pop($stack)));
            }
        }

        if (empty($stack)) {
            return '';
        }

        return array_pop($stack);
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
            if (in_array($metadata['interface'], $interfaces)) {
                $format           = 'CASE WHEN %1$s.%2$s IS NOT NULL THEN %1$s.%2$s ELSE \'\' END';
                $nameParts[$part] = sprintf($format, $relationAlias, $metadata['suggestedFieldName']);
            }
        }

        return $nameParts;
    }
}
