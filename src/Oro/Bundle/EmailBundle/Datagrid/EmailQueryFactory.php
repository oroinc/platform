<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EmailQueryFactory
{
    /** @var EmailOwnerProviderStorage */
    protected $emailOwnerProviderStorage;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var string */
    protected $fromEmailExpression;

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
     * @param EmailOwnerProviderStorage $emailOwnerProviderStorage
     * @param NameFormatter             $nameFormatter
     */
    public function __construct(EmailOwnerProviderStorage $emailOwnerProviderStorage, NameFormatter $nameFormatter)
    {
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
        $this->nameFormatter             = $nameFormatter;
    }

    /**
     * @param QueryBuilder $qb                  Source query builder
     * @param string       $emailFromTableAlias EmailAddress table alias of joined Email#fromEmailAddress association
     */
    public function prepareQuery(QueryBuilder $qb, $emailFromTableAlias = 'a')
    {
        $qb->addSelect($this->getFromEmailExpression($emailFromTableAlias));
        foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
            $fieldName = $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider);

            $qb->leftJoin(sprintf('%s.%s', $emailFromTableAlias, $fieldName), $fieldName);
        }
    }

    /**
     * @param string $emailFromTableAlias EmailAddress table alias of joined Email#fromEmailAddress association
     *
     * @return string
     */
    protected function getFromEmailExpression($emailFromTableAlias)
    {
        $nameFormat  = $this->nameFormatter->getNameFormat();
        $expressions = [];
        foreach ($this->emailOwnerProviderStorage->getProviders() as $provider) {
            $relationAlias = $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider);
            $nameParts     = $this->extractNamePartsPaths($provider->getEmailOwnerClass(), $relationAlias);

            $expressions[$relationAlias] = $this->buildExpression($nameFormat, $nameParts);
        }

        $expression = '';
        foreach ($expressions as $alias => $expressionPart) {
            $expression .= sprintf('WHEN %s.%s IS NOT NULL THEN %s', $emailFromTableAlias, $alias, $expressionPart);
        }

        return sprintf("(CASE %s ELSE '' END) as fromEmailExpression", $expression);
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
                    if ($key !== $lowerCaseKey) {
                        $nameParts[$lowerCaseKey] = sprintf('UPPER(%s)', $nameParts[$lowerCaseKey]);
                    }
                    $parts[] = $nameParts[$lowerCaseKey];
                }

                $parts[] = sprintf("'%s'", $prependSeparator);
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
    private function extractNamePartsPaths($className, $relationAlias)
    {
        $nameParts  = array_fill_keys(array_keys($this->namePartsMap), []);
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
