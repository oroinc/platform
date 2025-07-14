<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Action;

use Oro\Bundle\ActionBundle\Action\CreateDate;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateDateTest extends TestCase
{
    private const TIMEZONE = 'Europe/London';

    private CreateDate $action;

    #[\Override]
    protected function setUp(): void
    {
        $localeSettings = $this->createMock(LocaleSettings::class);
        $localeSettings->expects($this->any())
            ->method('getTimeZone')
            ->willReturn(self::TIMEZONE);

        $this->action = new CreateDate(new ContextAccessor(), $localeSettings);
        $this->action->setDispatcher($this->createMock(EventDispatcher::class));
    }

    public function testInitializeExceptionInvalidTime(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Option "date" must be a string, boolean given.');

        $this->action->initialize(['attribute' => new PropertyPath('test_attribute'), 'date' => true]);
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options, ?\DateTime $expectedResult = null): void
    {
        $context = new ItemStub([]);
        $attributeName = (string)$options['attribute'];
        $this->action->initialize($options);
        $this->action->execute($context);
        $this->assertNotNull($context->{$attributeName});
        $this->assertInstanceOf('DateTime', $context->{$attributeName});

        if ($expectedResult) {
            $this->assertEquals($expectedResult, $context->{$attributeName});
        }
    }

    public function executeDataProvider(): array
    {
        return [
            'without_date' => [
                'options' => [
                    'attribute' => new PropertyPath('test_attribute'),
                ],
            ],
            'with_date' => [
                'options' => [
                    'attribute' => new PropertyPath('test_attribute'),
                    'date'      => '2014-01-01',
                ],
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC'))
            ],
            'with_datetime' => [
                'options' => [
                    'attribute' => new PropertyPath('test_attribute'),
                    'date'      => '2014-01-01 12:12:12',
                ],
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC'))
            ],
        ];
    }
}
