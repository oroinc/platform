<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;

trait SqlWalkerHelperTrait
{
    /**
     * @return SelectStatement
     */
    protected function getDefaultAST()
    {
        $selectExpr = new SelectExpression('o', 'test', null);
        $selectClause = new SelectClause([$selectExpr], false);
        $rangeVarDeclaration = new RangeVariableDeclaration('Schema\Name', 'o');
        $from1 = new IdentificationVariableDeclaration($rangeVarDeclaration, null, []);
        $fromClause = new FromClause([$from1]);

        return new SelectStatement($selectClause, $fromClause);
    }

    /**
     * @return array
     */
    protected function getQueryComponents()
    {
        $rootMetadata = new ClassMetadata('Class\Name');
        $rootMetadata->setIdentifier(['o']);
        $otherMetadata = new ClassMetadata('Class\Name');
        $otherMetadata->setIdentifier(['i']);

        return [
            '_product' => [
                'metadata' => $otherMetadata,
            ],
            '_productUnitPrecision' => [
                'metadata' => $otherMetadata,
            ],
            '_warehouse' => [
                'metadata' => $otherMetadata,
            ],
            'o' => [
                'map' => null,
                'metadata' => $rootMetadata,
            ],
        ];
    }
}
