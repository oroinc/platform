<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\ORM\Query\ResultIterator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\IdentificationVariableDeclaration;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectExpression;
use Doctrine\ORM\Query\AST\SelectStatement;

use Oro\Bundle\ProductBundle\Entity\Product;

trait SqlWalkerHelperTrait
{
    /**
     * @return SelectStatement
     */
    protected function getDefaultAST()
    {
        $selectExpr = new SelectExpression('o', 'test', null);
        $selectClause = new SelectClause([$selectExpr], false);
        $rangeVarDeclaration = new RangeVariableDeclaration(Product::class, 'o');
        $from1 = new IdentificationVariableDeclaration($rangeVarDeclaration, null, []);
        $fromClause = new FromClause([$from1]);

        return new SelectStatement($selectClause, $fromClause);
    }

    /**
     * @return array
     */
    protected function getQueryComponents()
    {
        $rootMetadata = new ClassMetadata($this->getClassName());
        $rootMetadata->setIdentifier(['o']);
        $otherMetadata = new ClassMetadata($this->getClassName());
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

    /**
     * @return string
     */
    abstract protected function getClassName(): string;
}
