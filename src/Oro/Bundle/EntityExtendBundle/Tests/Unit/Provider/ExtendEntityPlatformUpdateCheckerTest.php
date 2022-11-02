<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendEntityPlatformUpdateChecker;

class ExtendEntityPlatformUpdateCheckerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var ExtendEntityPlatformUpdateChecker */
    private $platformUpdateChecker;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->platformUpdateChecker = new ExtendEntityPlatformUpdateChecker(
            $this->configManager
        );
    }

    private function getEntityConfig(string $scope, string $className, array $values): Config
    {
        return new Config(new EntityConfigId($scope, $className), $values);
    }

    public function testWhenNoSchemaChanges()
    {
        $extendConfig1 = $this->getEntityConfig(
            'extend',
            'Test\Entity1',
            ['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]
        );
        $extendConfig2 = $this->getEntityConfig(
            'extend',
            'Test\Entity2',
            ['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]
        );
        $extendConfig3 = $this->getEntityConfig(
            'extend',
            'Test\Entity3',
            ['is_extend' => false, 'state' => ExtendScope::STATE_UPDATE]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('extend', self::isNull(), self::isFalse())
            ->willReturn([$extendConfig1, $extendConfig2, $extendConfig3]);

        self::assertNull(
            $this->platformUpdateChecker->checkReadyToUpdate()
        );
    }

    public function testWhenSchemaChangesExists()
    {
        $extendConfig1 = $this->getEntityConfig(
            'extend',
            'Test\Entity1',
            ['is_extend' => true, 'state' => ExtendScope::STATE_NEW]
        );
        $extendConfig2 = $this->getEntityConfig(
            'extend',
            'Test\Entity2',
            ['is_extend' => true, 'state' => ExtendScope::STATE_UPDATE]
        );
        $extendConfig3 = $this->getEntityConfig(
            'extend',
            'Test\Entity3',
            ['is_extend' => true, 'state' => ExtendScope::STATE_ACTIVE]
        );
        $extendConfig4 = $this->getEntityConfig(
            'extend',
            'Test\Entity4',
            ['is_extend' => true, 'state' => ExtendScope::STATE_DELETE, 'is_deleted' => true]
        );
        $extendConfig5 = $this->getEntityConfig(
            'extend',
            'Test\Entity5',
            ['is_extend' => true, 'state' => ExtendScope::STATE_DELETE]
        );

        $this->configManager->expects(self::once())
            ->method('getConfigs')
            ->with('extend', self::isNull(), self::isFalse())
            ->willReturn([$extendConfig1, $extendConfig2, $extendConfig3, $extendConfig4, $extendConfig5]);

        self::assertSame(
            [
                'The entities configuration has not applied schema changes for the following entities:'
                . ' Test\Entity1, Test\Entity2, Test\Entity5.'
                . ' Please update schema using "oro:entity-extend:update" CLI command (--dry-run option is available).'
                . ' Please note, that schema depends on source code and you may need to rollback to previous version'
                . ' of the source code.'
            ],
            $this->platformUpdateChecker->checkReadyToUpdate()
        );
    }
}
