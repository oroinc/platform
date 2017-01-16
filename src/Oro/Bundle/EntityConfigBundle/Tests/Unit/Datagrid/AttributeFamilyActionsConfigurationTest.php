<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Datagrid\AttributeFamilyActionsConfiguration;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributeFamilyActionsConfigurationTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var int */
    const ENTITY_ID = 777;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    private $securityFacade;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    private $entityManager;

    /** @var AttributeFamilyActionsConfiguration */
    private $attributeFamilyActionsConfiguration;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeFamilyActionsConfiguration = new attributeFamilyActionsConfiguration(
            $this->securityFacade,
            $this->entityManager
        );
    }

    /**
     * @return array
     */
    public function isGrantedDataProvider()
    {
        return [
            'deletion is granted' => [
                'isGranted' => true
            ],
            'deletion is not granted' => [
                'isGranted' => false
            ]
        ];
    }

    /**
     * @dataProvider isGrantedDataProvider
     * @param bool $isGranted
     */
    public function testConfigureActionsVisibility($isGranted)
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, ['id' => self::ENTITY_ID]);

        $record = new ResultRecord(['id' => self::ENTITY_ID]);

        $this->entityManager
            ->expects($this->once())
            ->method('getReference')
            ->with(AttributeFamily::class, self::ENTITY_ID)
            ->willReturn($attributeFamily);

        $this->securityFacade
            ->expects($this->once())
            ->method('isGranted')
            ->with('delete', $attributeFamily)
            ->willReturn($isGranted);

        $this->assertEquals(
            [
                'view' => true,
                'edit' => true,
                'delete' => $isGranted
            ],
            $this->attributeFamilyActionsConfiguration->configureActionsVisibility($record)
        );
    }
}
