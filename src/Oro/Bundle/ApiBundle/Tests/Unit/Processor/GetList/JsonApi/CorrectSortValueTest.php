<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\JsonApi;

use Oro\Bundle\ApiBundle\Processor\GetList\BuildQuery;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Processor\GetList\JsonApi\CorrectSortValue;
use Oro\Bundle\ApiBundle\Request\RestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;

class CorrectSortValueTest extends OrmRelatedTestCase
{
    /** @var CorrectSortValue */
    protected $processor;

    /** @var GetListContext */
    protected $context;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = new CorrectSortValue($this->doctrineHelper);

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadataProvider = $this->getMockBuilder('Oro\Bundle\ApiBundle\Provider\MetadataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context          = new GetListContext($this->configProvider, $this->metadataProvider);
    }

    public function testProcessOnExistingQuery()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->setQuery($qb);

        $this->processor->process($this->context);

        $this->assertSame($qb, $this->context->getQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className      = 'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User';
        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper->expects($this->once())
            ->method('isManageableEntityClass')
            ->with($className)
            ->willReturn(false);
        $doctrineHelper->expects($this->never())
            ->method('getEntityMetadataForClass');

        $processor = new BuildQuery($doctrineHelper);
        $this->context->setClassName($className);
        $processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    /**
     * @dataProvider processProvider
     */
    public function testProcess($className, $requestString, $expectedResult)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->once())
            ->method('getQueryString')
            ->willReturn('sort=' . $requestString);

        $this->context->setFilterValues(new RestFilterValueAccessor($request));
        $this->context->setClassName($className);

        $this->processor->process($this->context);

        $filterValues = $this->context->getFilterValues();
        $filterValue  = $filterValues->get('sort');
        $this->assertEquals($expectedResult, $filterValue->getValue());
    }

    public function processProvider()
    {
        return [
            'entity with "id" index field and "id" sorter request string'          => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'id',
                'id'
            ],
            'entity with "id" index field and "-id" sorter request string'         => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                '-id',
                '-id'
            ],
            'entity with "id" index field and "id,label" sorter request string'    => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User',
                'id,label',
                'id,label'
            ],
            'entity with "name" index field and "id" sorter request string'        => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                'id',
                'name'
            ],
            'entity with "name" index field and "-id" sorter request string'       => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                '-id',
                '-name'
            ],
            'entity with "name" index field and "-id,label" sorter request string' => [
                'Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Category',
                '-id,label',
                '-name,label'
            ]
        ];
    }
}
