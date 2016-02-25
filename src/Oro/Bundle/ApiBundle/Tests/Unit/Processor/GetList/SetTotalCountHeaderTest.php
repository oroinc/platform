<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\SetTotalCountHeader;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class SetTotalCountHeaderTest extends OrmRelatedTestCase
{
    /** @var SetTotalCountHeader */
    protected $processor;

    /** @var GetListContext */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $countQueryBuilderOptimizer;

    protected function setUp()
    {
        parent::setUp();

        $configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = new GetListContext($configProvider, $metadataProvider);

        $this->countQueryBuilderOptimizer = $this
            ->getMockBuilder('Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SetTotalCountHeader($this->countQueryBuilderOptimizer);
    }

    public function testProcessWithoutRequestHeader()
    {
        $this->processor->process($this->context);
        $this->assertFalse($this->context->getResponseHeaders()->has('X-Include-Total-Count'));
    }

    public function testProcessOnExistingHeader()
    {
        $this->context->getResponseHeaders()->set('X-Include-Total-Count', 13);
        $this->processor->process($this->context);
        $this->assertEquals(13, $this->context->getResponseHeaders()->get('X-Include-Total-Count'));
    }

    public function testProcessWithTotalCallback()
    {
        $testCount = 135;
        $this->context->setTotalCountCallback(
            function () use ($testCount) {
                return $testCount;
            }
        );
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->processor->process($this->context);
        $this->assertEquals($testCount, $this->context->getResponseHeaders()->get('X-Include-Total-Count'));
    }

    public function testProcessWithWrongTotalCallback()
    {
        $this->context->setTotalCountCallback(new \stdClass());
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Expected callable for "totalCount", "stdClass" given.'
        );
        $this->processor->process($this->context);
    }

    public function testProcessWithWrongTotalCallbackResult()
    {
        $this->context->setTotalCountCallback(
            function () {
                return 'non integer value';
            }
        );
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Expected integer as result of "totalCount" callback, "string" given.'
        );
        $this->processor->process($this->context);
    }

    public function testProcessQueryBuilder()
    {
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group';
        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->context->setQuery($query);
        $this->countQueryBuilderOptimizer->expects($this->once())
            ->method('getCountQueryBuilder')
            ->willReturnCallback(
                function (QueryBuilder $qb) {
                    $qb->setMaxResults(10);
                    return $qb;

                }
            );
        $this->processor->process($this->context);

        // mocked fetchColumn method in StatementMock returns null value (0 records in db)
        $this->assertEquals(0, $this->context->getResponseHeaders()->get('X-Include-Total-Count'));
    }

    public function testProcessQuery()
    {
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $entityClass = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group';
        $query = $this->doctrineHelper->getEntityRepositoryForClass($entityClass)->createQueryBuilder('e');
        $this->context->setQuery($query->getQuery());
        $this->processor->process($this->context);

        // mocked fetchColumn method in StatementMock returns null value (0 records in db)
        $this->assertEquals(0, $this->context->getResponseHeaders()->get('X-Include-Total-Count'));
    }

    public function testProcessOnWrongQuery()
    {
        $this->context->getRequestHeaders()->set('X-Include', ['totalCount']);
        $this->context->setQuery(new \stdClass());
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Expected instance of Doctrine\ORM\QueryBuilder, Doctrine\ORM\Query, '
            . 'Oro\Bundle\EntityBundle\ORM\SqlQueryBuilder or Oro\Bundle\EntityBundle\ORM\SqlQuery, '
            . '"stdClass" given.'
        );
        $this->processor->process($this->context);
    }
}
