<?php

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Component\DoctrineUtils\ORM\DqlUtil;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class DqlUtilTest extends OrmTestCase
{
    /** @var EntityManager */
    protected $em;

    public function setUp()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity'
        );

        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $this->em->getConfiguration()->setEntityNamespaces(
            [
                'Test' => 'Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity'
            ]
        );
    }

    /**
     * @dataProvider hasParameterDataProvider
     *
     * @param string $dql
     * @param string $parameter
     * @param bool $expected
     */
    public function testHasParameter($dql, $parameter, $expected)
    {
        $this->assertEquals($expected, DqlUtil::hasParameter($dql, $parameter));
    }

    public function hasParameterDataProvider()
    {
        $dql = 'SELECT a.name FROM Some:Other as a WHERE a.name = :param1'
            . ' AND a.name != :param2 AND a.status = ?1';

        return [
            [$dql, 'param1', true],
            [$dql, 'param5', false],
            [$dql, 'param11', false],
            [$dql, 0, false],
            [$dql, 1, true],
        ];
    }

    /**
     * @dataProvider getAliasesDataProvider
     */
    public function testGetAliases(callable $dqlFactory, array $expectedAliases)
    {
        $this->assertEquals($expectedAliases, DqlUtil::getAliases($dqlFactory($this->em)));
    }

    public function getAliasesDataProvider()
    {
        return [
            'query with fully qualified entity name' => [
                function (EntityManager $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from('Oro\Component\DoctrineUtils\Tests\Unit\Fixtures\Entity\Person', 'p')
                        ->join('p.bestItem', 'i')
                        ->getDQL();
                },
                ['p', 'i'],
            ],
            'query aliased entity name' => [
                function (EntityManager $em) {
                    return $em->createQueryBuilder()
                        ->select('p')
                        ->from('Test:Person', 'p')
                        ->join('p.bestItem', 'i')
                        ->getDQL();
                },
                ['p', 'i'],
            ],
            'query with subquery' => [
                function (EntityManager $em) {
                    $qb = $em->createQueryBuilder();

                    return $qb
                        ->select('p')
                        ->from('Test:Person', 'p')
                        ->join('p.bestItem', 'i')
                        ->where(
                            $qb->expr()->exists(
                                $em->createQueryBuilder()
                                    ->select('p2')
                                    ->from('Test:Person', 'p2')
                                    ->join('p2.groups', '_g2')
                                    ->where('p2.id = p.id')
                            )
                        )
                        ->getDQL();
                },
                ['p', 'i', 'p2', '_g2'],
            ],
            'query with newlines after aliases, AS keyword and case insensitive' => [
                function () {
                    return <<<DQL
SELECT  p
FROM  TestPerson  p
JOIN  p.bestItem  AS  i
WHERE EXISTS(
    SELECT p2
    FROM TestPerson  p2
    join  p2.groups  _g2
    WHERE  p2.id  =  p.id
)
DQL
                        ;
                },
                ['p', 'i', 'p2', '_g2'],
            ],
        ];
    }

    /**
     * @dataProvider replaceAliasesProvider
     */
    public function testReplaceAliases($dql, array $replacements, $expectedDql)
    {
        $this->assertEquals($expectedDql, DqlUtil::replaceAliases($dql, $replacements));
    }

    public function replaceAliasesProvider()
    {
        return [
            [
                <<<DQL
SELECT eu.id
FROM OroEmailBundle:EmailUser eu
LEFT JOIN eu.email e
LEFT JOIN eu.mailboxOwner mb
LEFT JOIN e.recipients r_to
LEFT JOIN eu.folders f
LEFT JOIN f.origin o
LEFT JOIN e.emailBody eb
WHERE (EXISTS(
    SELECT 1
    FROM OroEmailBundle:EmailOrigin _eo
    JOIN _eo.folders _f
    JOIN _f.emailUsers _eu
    WHERE _eo.isActive = true AND _eu.id = eu.id
))
AND e.head = true AND (eu.owner = :owner AND eu.organization  = :organization)
AND e.subject LIKE :subject1027487935
DQL
                ,
                [
                    ['eu', 'eur'],
                    ['e', 'er'],
                    ['mb', 'mbr'],
                    ['r_to', 'r_tor'],
                    ['f', 'fr'],
                    ['o', 'or'],
                    ['eb', 'ebr'],
                    ['_eo', '_eor'],
                    ['_f', '_fr'],
                    ['_eu', '_eur'],
                ],
                <<<DQL
SELECT eur.id
FROM OroEmailBundle:EmailUser eur
LEFT JOIN eur.email er
LEFT JOIN eur.mailboxOwner mbr
LEFT JOIN er.recipients r_tor
LEFT JOIN eur.folders fr
LEFT JOIN fr.origin or
LEFT JOIN er.emailBody ebr
WHERE (EXISTS(
    SELECT 1
    FROM OroEmailBundle:EmailOrigin _eor
    JOIN _eor.folders _fr
    JOIN _fr.emailUsers _eur
    WHERE _eor.isActive = true AND _eur.id = eur.id
))
AND er.head = true AND (eur.owner = :owner AND eur.organization  = :organization)
AND er.subject LIKE :subject1027487935
DQL
            ],
        ];
    }

    /**
     * @dataProvider buildConcatExprProvider
     *
     * @param string[] $parts
     * @param string   $expectedExpr
     */
    public function testBuildConcatExpr($parts, $expectedExpr)
    {
        $this->assertEquals($expectedExpr, DqlUtil::buildConcatExpr($parts));
    }

    public function buildConcatExprProvider()
    {
        return [
            [[], ''],
            [[''], ''],
            [['a.field1'], 'a.field1'],
            [['a.field1', 'a.field2'], 'CONCAT(a.field1, a.field2)'],
            [['a.field1', 'a.field2', 'a.field3'], 'CONCAT(a.field1, CONCAT(a.field2, a.field3))'],
            [['a.field1', '\' \'', 'a.field3'], 'CONCAT(a.field1, CONCAT(\' \', a.field3))'],
        ];
    }
}
