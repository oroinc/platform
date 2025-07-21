<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Datagrid\AttributeFamilyActionsConfiguration;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AttributeFamilyActionsConfigurationTest extends TestCase
{
    use EntityTrait;

    private const ENTITY_ID = 777;

    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private EntityManager&MockObject $entityManager;
    private AttributeFamilyActionsConfiguration $attributeFamilyActionsConfiguration;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);

        $this->attributeFamilyActionsConfiguration = new attributeFamilyActionsConfiguration(
            $this->authorizationChecker,
            $this->entityManager
        );
    }

    public function isGrantedDataProvider(): array
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
     */
    public function testConfigureActionsVisibility(bool $isGranted): void
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, ['id' => self::ENTITY_ID]);

        $record = new ResultRecord(['id' => self::ENTITY_ID]);

        $this->entityManager->expects($this->once())
            ->method('getReference')
            ->with(AttributeFamily::class, self::ENTITY_ID)
            ->willReturn($attributeFamily);

        $this->authorizationChecker->expects($this->once())
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
