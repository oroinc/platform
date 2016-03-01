<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Engine\Orm\PdoMysql;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parser;

use Oro\Bundle\DataGridBundle\Engine\Orm\PdoMysql\GroupConcat;

class GroupConcatTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GroupConcat
     */
    protected $function;

    protected function setUp()
    {
        $this->function = new GroupConcat('name');
    }

    /**
     * @param string $dql
     * @param string $expectedSql
     * @param array  $exception
     *
     * @dataProvider parseDataProvider
     */
    public function testParse($dql, $expectedSql, array $exception)
    {
        if (!empty($exception)) {
            list($exception, $message) = $exception;

            $this->setExpectedException($exception, $message);
        }

        $configuration = $this->getMockBuilder('Doctrine\ORM\Configuration')
            ->disableOriginalConstructor()
            ->getMock();
        $configuration->expects($this->once())
            ->method('getDefaultQueryHints')
            ->will($this->returnValue([]));
        $configuration->expects($this->once())
            ->method('isSecondLevelCacheEnabled')
            ->will($this->returnValue(false));

        $em = $this
            ->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('getConfiguration')
            ->will($this->returnValue($configuration));

        $query = new Query($em);
        $query->setDQL($dql);

        $parser = new Parser($query);

        $this->function->parse($parser);

        $sqlWalker = $this
            ->getMockBuilder('Doctrine\ORM\Query\SqlWalker')
            ->disableOriginalConstructor()
            ->getMock();

        $sqlWalker
            ->expects($this->any())
            ->method('walkPathExpression')
            ->will($this->returnValue('field'));

        $sqlWalker
            ->expects($this->any())
            ->method('walkOrderByClause')
            ->will($this->returnValue('ORDER BY orderField'));

        $sqlWalker
            ->expects($this->any())
            ->method('walkStringPrimary')
            ->will($this->returnValue('\', \''));

        $this->assertEquals(
            $expectedSql,
            $this->function->getSql($sqlWalker)
        );
    }

    /**
     * @return array
     */
    public function parseDataProvider()
    {
        return [
            'empty'                => [
                'dql'         => '()',
                'expectedSql' => '',
                'exception'   => [
                    'Doctrine\ORM\Query\QueryException',
                    'Expected Doctrine\ORM\Query\Lexer::T_CLOSE_PARENTHESIS, got end of string'
                ]
            ],
            'no_order_distinct'    => [
                'dql'         => '(DISTINCT contactGroup.label SEPARATOR \', \')',
                'expectedSql' => 'GROUP_CONCAT(DISTINCT field SEPARATOR \', \')',
                'exception'   => []
            ],
            'no_order_no_distinct' => [
                'dql'         => '(contactGroup.label SEPARATOR \', \')',
                'expectedSql' => 'GROUP_CONCAT(field SEPARATOR \', \')',
                'exception'   => []
            ],
            'order_no_distinct'    => [
                'dql'         => '(contactGroup.label ORDER BY contactGroup.label SEPARATOR \', \')',
                'expectedSql' => 'GROUP_CONCAT(field ORDER BY orderField SEPARATOR \', \')',
                'exception'   => []
            ],
            'no_separator'         => [
                'dql'         => '(contactGroup.label ORDER BY contactGroup.label)',
                'expectedSql' => 'GROUP_CONCAT(field ORDER BY orderField)',
                'exception'   => []
            ],
            'wrong_separator'      => [
                'dql'         => '(contactGroup.label ORDER BY contactGroup.label TEST)',
                'expectedSql' => '',
                'exception'   => [
                    'Doctrine\ORM\Query\QueryException',
                    'Expected separator'
                ]
            ],
            'multiple'             => [
                'dql'         => '(contactGroup.label, contactGroup.id SEPARATOR \', \')',
                'expectedSql' => 'GROUP_CONCAT(field, field SEPARATOR \', \')',
                'exception'   => []
            ],
            'full'                 => [
                'dql'         => '(contactGroup.label ORDER BY contactGroup.label SEPARATOR \', \')',
                'expectedSql' => 'GROUP_CONCAT(field ORDER BY orderField SEPARATOR \', \')',
                'exception'   => []
            ]
        ];
    }
}
