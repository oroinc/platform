<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Grid;

use Oro\Bundle\DataGridBundle\Tests\Unit\Datagrid\DatagridGuesserMock;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Grid\ConfigurationProvider;
use Oro\Bundle\SegmentBundle\Grid\SegmentDatagridConfigurationBuilder;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;

class ConfigurationProviderTest extends SegmentDefinitionTestCase
{
    const TEST_GRID_NAME = 'test';

    /** @var ConfigurationProvider */
    protected $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    protected function setUp()
    {
        $this->doctrine = $this->getDoctrine(
            [self::TEST_ENTITY => []],
            [self::TEST_ENTITY => [self::TEST_IDENTIFIER_NAME]]
        );

        $this->configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();

        $entityNameResolver = $this->getMockBuilder(EntityNameResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $builder = new SegmentDatagridConfigurationBuilder(
            $this->getFunctionProvider(),
            $this->getVirtualFieldProvider(),
            $this->doctrine,
            new DatagridGuesserMock(),
            $entityNameResolver
        );

        $builder->setConfigManager($this->configManager);

        $this->provider = new ConfigurationProvider(
            $builder,
            $this->doctrine
        );
    }

    protected function tearDown()
    {
        unset($this->provider, $this->doctrine, $this->configManager);
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->provider->isApplicable(Segment::GRID_PREFIX . '2'));
        $this->assertFalse($this->provider->isApplicable(Report::GRID_PREFIX . '2'));
    }

    public function testGetConfiguration()
    {
        $metadata = new EntityMetadata('Oro\Bundle\UserBundle\Entity\User');
        $this->configManager->expects($this->once())
            ->method('getEntityMetadata')
            ->will($this->returnValue($metadata));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('find')->with(2)
            ->will($this->returnValue($this->getSegment()));

        $this->doctrine->expects($this->once())->method('getRepository')->with('OroSegmentBundle:Segment')
            ->will($this->returnValue($repository));

        $result = $this->provider->getConfiguration(Segment::GRID_PREFIX . '2');

        $this->assertInstanceOf('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration', $result);
    }

    /**
     * @dataProvider definitionProvider
     *
     * @param mixed $definition
     * @param bool  $expectedResult
     */
    public function testIsConfigurationValid($definition, $expectedResult)
    {
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('find')->with(2)
            ->will($this->returnValue($this->getSegment(false, $definition)));

        $this->doctrine->expects($this->once())->method('getRepository')->with('OroSegmentBundle:Segment')
            ->will($this->returnValue($repository));
        $result = $this->provider->isConfigurationValid(Segment::GRID_PREFIX . '2');
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function definitionProvider()
    {
        return [
            'valid'     => [$this->getDefaultDefinition(), true],
            'not valid' => [['empty array'], false]
        ];
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Segment id not found in "oro_segment_grid_" gridName.
     */
    public function testDoNotProcessInvalidSegmentGridName()
    {
        $this->provider->getConfiguration(Segment::GRID_PREFIX);
    }
}
