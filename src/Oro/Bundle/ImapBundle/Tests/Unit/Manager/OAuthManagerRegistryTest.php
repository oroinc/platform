<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Oro\Bundle\ImapBundle\Manager\OAuthManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuthManagerRegistry;

class OAuthManagerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return OAuthManagerInterface[]|\PHPUnit\Framework\MockObject\MockObject[]
     */
    private function getMockedManagers(): array
    {
        return array_map(function (string $type) {
            $oauthManager = $this->createMock(OAuthManagerInterface::class);
            $oauthManager->expects($this->any())
                ->method('getType')
                ->willReturn($type);

            return $oauthManager;
        }, ['manager_1', 'manager_2', 'manager_3']);
    }

    public function testManagerRegistry(): void
    {
        $managers = $this->getMockedManagers();
        $registry = new OAuthManagerRegistry($managers);

        $types = [];
        foreach ($managers as $manager) {
            $type = $manager->getType();
            $types[] = $type;
            $this->assertSame($manager, $registry->getManager($type));
            $this->assertTrue($registry->hasManager($type));
        }

        $this->assertFalse($registry->hasManager('non_existing_type'));
        $this->assertEquals($types, $registry->getTypes());
        $this->assertEquals($managers, $registry->getManagers());
    }

    public function testDuplicateManagerException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The manager for "manager_1" already exists.');

        $duplicateManager = $this->createMock(OAuthManagerInterface::class);
        $duplicateManager->expects($this->any())
            ->method('getType')
            ->willReturn('manager_1');

        new OAuthManagerRegistry(array_merge($this->getMockedManagers(), [$duplicateManager]));
    }

    public function testNonExistingManagerException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The manager for "manager_1" does not exist.');

        $registry = new OAuthManagerRegistry([]);
        $registry->getManager('manager_1');
    }

    /**
     * @dataProvider getEnabledAssertionsData
     */
    public function testIsOauthImapEnabled(array $availability, bool $expected, ?string $type): void
    {
        $managers = $this->getMockedManagers();
        foreach ($managers as $index => $manager) {
            $managerAvailability = $availability[$index];
            $manager->expects($this->any())
                ->method('isOAuthEnabled')
                ->willReturn($managerAvailability);
        }

        $registry = new OAuthManagerRegistry($managers);

        $this->assertEquals($expected, $registry->isOauthImapEnabled($type));
    }

    public function getEnabledAssertionsData(): array
    {
        return [
            [
                [true, true, true],
                true,
                null
            ],
            [
                [true, true, true],
                true,
                'manager_1'
            ],
            [
                [true, true, true],
                true,
                'manager_2'
            ],
            [
                [true, true, true],
                true,
                'manager_3'
            ],
            [
                [true, true, false],
                true,
                null
            ],
            [
                [true, true, false],
                true,
                'manager_1'
            ],
            [
                [true, true, false],
                true,
                'manager_2'
            ],
            [
                [true, true, false],
                false,
                'manager_3'
            ],
            [
                [true, false, false],
                true,
                null
            ],
            [
                [false, false, false],
                false,
                null
            ]
        ];
    }
}
