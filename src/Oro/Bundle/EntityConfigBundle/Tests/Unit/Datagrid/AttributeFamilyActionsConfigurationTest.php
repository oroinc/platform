<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Datagrid\AttributeFamilyActionsConfiguration;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AttributeFamilyActionsConfigurationTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var int */
    const ENTITY_ID = 777;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var AttributeFamilyActionsConfiguration */
    private $attributeFamilyActionsConfiguration;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->attributeFamilyActionsConfiguration = new attributeFamilyActionsConfiguration(
            $this->authorizationChecker,
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

        $this->authorizationChecker
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
