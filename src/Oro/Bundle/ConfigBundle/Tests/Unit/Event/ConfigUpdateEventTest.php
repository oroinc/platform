<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Event;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use PHPUnit\Framework\TestCase;

class ConfigUpdateEventTest extends TestCase
{
    private const SCOPE = 'scope';
    private const SCOPE_ID = 123;
    private const CHANGE_SET = [
        'oro_user.key1' => ['old' => 'old value', 'new' => 'default value'],
        'oro_user.key2' => ['old' => 'pre value', 'new' => 50]
    ];

    private ConfigUpdateEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->event = new ConfigUpdateEvent(self::CHANGE_SET, self::SCOPE, self::SCOPE_ID);
    }

    public function testGetChangeSet(): void
    {
        self::assertEquals(self::CHANGE_SET, $this->event->getChangeSet());
    }

    public function testGetScope(): void
    {
        self::assertEquals(self::SCOPE, $this->event->getScope());
    }

    public function testGetScopeId(): void
    {
        self::assertEquals(self::SCOPE_ID, $this->event->getScopeId());
    }

    /**
     * @dataProvider isChangedDataProvider
     */
    public function testIsChanged(string $key, bool $result): void
    {
        self::assertSame($result, $this->event->isChanged($key));
    }

    public function isChangedDataProvider(): array
    {
        return [
            'oro_user.key1 changed' => ['oro_user.key1', true],
            'oro_user.key2 changed' => ['oro_user.key2', true],
            'oro_user.notChanged'   => ['oro_user.notChanged', false],
        ];
    }

    public function testGetOldValue(): void
    {
        self::assertEquals('old value', $this->event->getOldValue('oro_user.key1'));
    }

    public function testGetNewValue(): void
    {
        self::assertEquals('default value', $this->event->getNewValue('oro_user.key1'));
    }

    public function testGetOldValueForNotChangedOption(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Could not retrieve a value for "oro_user.notChanged".');

        $this->event->getOldValue('oro_user.notChanged');
    }

    public function testGetNewValueForNotChangedOption(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Could not retrieve a value for "oro_user.notChanged".');

        $this->event->getNewValue('oro_user.notChanged');
    }
}
