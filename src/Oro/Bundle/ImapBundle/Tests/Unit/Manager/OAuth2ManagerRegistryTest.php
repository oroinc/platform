<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Manager;

use Oro\Bundle\ImapBundle\Manager\Oauth2ManagerInterface;
use Oro\Bundle\ImapBundle\Manager\OAuth2ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OAuth2ManagerRegistryTest extends TestCase
{
    /** @var OAuth2ManagerRegistry */
    private $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->registry = new OAuth2ManagerRegistry();
    }

    public function testManagerRegistry(): void
    {
        $types = $managers = [];
        foreach ($this->getMockedManagers() as $manager) {
            $types[] = $type = $manager->getType();
            $managers[] = $manager;
            $instance = $this->registry->addManager($manager);
            $this->assertInstanceOf(OAuth2ManagerRegistry::class, $instance);
            $this->assertSame($this->registry, $instance);

            $this->assertSame($manager, $this->registry->getManager($type));
            $this->assertTrue($this->registry->hasManager($type));
        }

        $this->assertFalse($this->registry->hasManager('non_existing_type'));
        $this->assertEquals($types, $this->registry->getTypes());
        $this->assertEquals($managers, $this->registry->getManagers());
    }

    public function testDuplicateManagerException(): void
    {
        foreach ($this->getMockedManagers() as $manager) {
            $this->registry->addManager($manager);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manager for type manager_1 already exists');

        /** @var MockObject|Oauth2ManagerInterface $duplicateManager */
        $duplicateManager = $this->createMock(Oauth2ManagerInterface::class);
        $duplicateManager->expects($this->any())
            ->method('getType')
            ->willReturn('manager_1');

        $this->registry->addManager($duplicateManager);
    }

    public function testNonExistingManagerException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manager for type manager_1 does not exists');
        $this->registry->getManager('manager_1');
    }

    /**
     * @return array|MockObject[]|Oauth2ManagerInterface[]
     */
    private function getMockedManagers(): array
    {
        return array_map(function (string $type) {
            $mock = $this->createMock(Oauth2ManagerInterface::class);
            $mock->expects($this->any())
                ->method('getType')
                ->willReturn($type);

            return $mock;
        }, ['manager_1', 'manager_2', 'manager_3']);
    }

    /**
     * @param array $availability
     * @param bool $expected
     * @param null|string $type
     *
     * @dataProvider getEnabledAssertionsData
     */
    public function testIsOauthImapEnabled(array $availability, bool $expected, ?string $type): void
    {
        /** @var MockObject|Oauth2ManagerInterface $managerMock */
        foreach ($this->getMockedManagers() as $index => $managerMock) {
            $managerAvailability = $availability[$index];
            $managerMock->expects($this->any())
                ->method('isOAuthEnabled')
                ->willReturn($managerAvailability);
            $this->registry->addManager($managerMock);
        }

        $this->assertEquals($expected, $this->registry->isOauthImapEnabled($type));
    }

    /**
     * @return array
     */
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
