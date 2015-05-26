<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EmailQueryFactory
{
    /** @var EmailOwnerProviderStorage */
    protected $emailOwnerProviderStorage;

    /** @var DQLNameFormatter */
    protected $formatter;

    /** @var string */
    protected $fromEmailExpression;

    /**
     * @param EmailOwnerProviderStorage                     $emailOwnerProviderStorage
     * @param \Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter $formatter
     */
    public function __construct(EmailOwnerProviderStorage $emailOwnerProviderStorage, DQLNameFormatter $formatter)
    {
        $this->emailOwnerProviderStorage = $emailOwnerProviderStorage;
        $this->formatter                 = $formatter;
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
        $providers = $this->emailOwnerProviderStorage->getProviders();
        if (empty($providers)) {
            return sprintf('%s.email', $emailFromTableAlias);
        }

        $expressionsByOwner = [];
        foreach ($providers as $provider) {
            $relationAlias                      = $this->emailOwnerProviderStorage->getEmailOwnerFieldName($provider);
            $expressionsByOwner[$relationAlias] = $this->formatter->getFormattedNameDQL(
                $relationAlias,
                $provider->getEmailOwnerClass()
            );
        }

        $expression = '';
        foreach ($expressionsByOwner as $alias => $expressionPart) {
            $expression .= sprintf('WHEN %s.%s IS NOT NULL THEN %s', $emailFromTableAlias, $alias, $expressionPart);
        }
        $expression = sprintf('CASE %s ELSE \'\' END', $expression);

        // if has owner then use expression to expose formatted name, use email otherwise
        return sprintf(
            'CONCAT(\'\', CASE WHEN %1$s.hasOwner = true THEN (%2$s) ELSE %1$s.email END) as fromEmailExpression',
            $emailFromTableAlias,
            $expression
        );
    }
}
