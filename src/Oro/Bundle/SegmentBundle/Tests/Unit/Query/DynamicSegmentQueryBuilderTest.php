<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;

use Oro\Bundle\EntityBundle\Provider\ConfigVirtualFieldProvider;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;

class DynamicSegmentQueryBuilderTest extends SegmentDefinitionTestCase
{
    /** @var FormFactoryInterface */
    private $formFactory;

    protected function setUp()
    {
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())->method('trans')->will($this->returnArgument(0));

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions(
                [
                new PreloadedExtension(
                    [
                    'oro_type_text_filter' => new TextFilterType($translator),
                    'oro_type_filter'      => new FilterType($translator),
                    ],
                    []
                ),
                new CsrfExtension(
                    $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface')
                )
                ]
            )
            ->getFormFactory();
    }

    protected function tearDown()
    {
        unset($this->formFactory);
    }

    public function testBuild()
    {
        $segment = $this->getSegment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));

        $doctrine = $this->getDoctrine(
            [self::TEST_ENTITY => ['username' => 'string', 'email' => 'string']],
            [self::TEST_ENTITY => [self::TEST_IDENTIFIER_NAME]]
        );
        $builder  = $this->getQueryBuilder($doctrine);
        /** @var \PHPUnit_Framework_MockObject_MockObject $em */
        $em = $doctrine->getManagerForClass(self::TEST_ENTITY);
        $qb = new QueryBuilder($em);
        $this->mockConnection($em);
        $em->expects($this->any())->method('createQueryBuilder')
            ->will($this->returnValue($qb));
        $em->expects($this->any())->method('getExpressionBuilder')
            ->will($this->returnValue(new Expr()));
        $em->expects($this->any())->method('createQuery')
            ->will($this->returnValue(new Query($em)));

        $builder->build($segment);

        $result  = $qb->getDQL();
        $counter = 0;
        $result  = preg_replace_callback(
            '/(:[a-z]+)(\d+)/',
            function ($matches) use (&$counter) {
                return $matches[1] . (++$counter);
            },
            $result
        );
        $result  = preg_replace('/(ts)(\d+)/', 't1', $result);
        $this->assertSame(
            sprintf(
                'SELECT DISTINCT t1.%s FROM %s t1 WHERE t1.email LIKE :string1',
                self::TEST_IDENTIFIER_NAME,
                self::TEST_ENTITY
            ),
            $result
        );
    }

    public function testBuildExtended()
    {
        $segment = $this->getSegment(
            false,
            [
            'columns'          => [
                [
                    'name'    => 'id',
                    'label'   => 'Id',
                ],
                [
                    'name'    => 'userName',
                    'label'   => 'User name',
                    'func'    => null,
                    'sorting' => 'ASC'
                ]
            ],
            'grouping_columns' => [['name' => 'id']],
            'filters'          => [
                [
                    'columnName' => 'address+AcmeBundle:Address::zip',
                    'criterion'  => [
                        'filter' => 'string',
                        'data'   => [
                            'type'  => 1,
                            'value' => 'zip_code'
                        ]
                    ]
                ],
                'AND',
                [
                    'columnName' => 'status+AcmeBundle:Status::code',
                    'criterion'  => [
                        'filter' => 'string',
                        'data'   => [
                            'type'  => 1,
                            'value' => 'code'
                        ]
                    ]
                ]
            ]
            ]
        );
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));

        $doctrine = $this->getDoctrine(
            [
            self::TEST_ENTITY    => [
                'username' => 'string',
                'email'    => 'string',
                'address'  => ['id'],
                'status'   => ['id'],
            ],
            'AcmeBundle:Address' => ['zip' => 'string'],
            'AcmeBundle:Status'  => ['code' => 'string'],
            ],
            [self::TEST_ENTITY => [self::TEST_IDENTIFIER_NAME]]
        );
        $builder  = $this->getQueryBuilder($doctrine);
        /** @var \PHPUnit_Framework_MockObject_MockObject $em */
        $em = $doctrine->getManagerForClass(self::TEST_ENTITY);
        $qb = new QueryBuilder($em);
        $this->mockConnection($em);
        $em->expects($this->any())->method('createQueryBuilder')
            ->will($this->returnValue($qb));
        $em->expects($this->any())->method('getExpressionBuilder')
            ->will($this->returnValue(new Expr()));
        $em->expects($this->any())->method('createQuery')
            ->will($this->returnValue(new Query($em)));

        $builder->build($segment);

        $this->assertEmpty($qb->getDQLPart('groupBy'));
        $this->assertEmpty($qb->getDQLPart('orderBy'));
        $this->assertNotEmpty($qb->getDQLPart('join'));
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $doctrine
     *
     * @return DynamicSegmentQueryBuilder
     */
    protected function getQueryBuilder(\PHPUnit_Framework_MockObject_MockObject $doctrine = null)
    {
        $manager = $this->getMockBuilder('Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager')
            ->disableOriginalConstructor()
            ->getMock();
        $manager->expects($this->any())
            ->method('createFilter')
            ->will(
                $this->returnCallback(
                    function ($name, $params) {
                        return $this->createFilter($name, $params);
                    }
                )
            );

        $entityHierarchyProvider = $this->getMock('Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface');
        $entityHierarchyProvider
            ->expects($this->any())
            ->method('getHierarchy')
            ->will($this->returnValue([]));

        $virtualFieldProvider = new ConfigVirtualFieldProvider($entityHierarchyProvider, []);

        $doctrine = $doctrine ? : $this->getDoctrine();
        $builder  = new DynamicSegmentQueryBuilder(
            new RestrictionBuilder($manager),
            $manager,
            $virtualFieldProvider,
            $doctrine
        );

        return $builder;
    }


    /**
     * Creates a new instance of a filter based on a configuration
     * of a filter registered in this manager with the given name
     *
     * @param string $name   A filter name
     * @param array  $params An additional parameters of a new filter
     *
     * @return FilterInterface
     * @throws \Exception
     */
    public function createFilter($name, array $params = null)
    {
        $defaultParams = [
            'type' => $name
        ];
        if ($params !== null && !empty($params)) {
            $params = array_merge($defaultParams, $params);
        }

        switch ($name) {
            case 'string':
                $filter = new StringFilter($this->formFactory, new FilterUtility());
                break;
            default:
                throw new \Exception(sprintf('Not implemented in this test filter: "%s" . ', $name));
        }
        $filter->init($name, $params);

        return $filter;
    }

    /**
     * @param $em
     */
    protected function mockConnection($em)
    {
        $connection = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('getDatabasePlatform')
            ->will($this->returnValue(null));
        $em->expects($this->any())->method('getConnection')
            ->will($this->returnValue($connection));
    }
}
