<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Unit\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Async\Topic\DeleteOldNumberSequenceTopic;
use Oro\Bundle\PlatformBundle\Command\Cron\DeleteOldNumberSequenceCronCommand;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Entity\Repository\NumberSequenceRepository;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DeleteOldNumberSequenceCronCommandTest extends TestCase
{
    private NumberSequenceRepository&MockObject $repository;
    private MessageProducerInterface&MockObject $messageProducer;
    private ManagerRegistry&MockObject $doctrine;
    private DeleteOldNumberSequenceCronCommand $command;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(NumberSequenceRepository::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->doctrine
            ->expects(self::any())
            ->method('getRepository')
            ->with(NumberSequence::class)
            ->willReturn($this->repository);

        $this->command = new DeleteOldNumberSequenceCronCommand(
            $this->doctrine,
            $this->messageProducer
        );
    }

    public function testGetDefaultDefinition(): void
    {
        self::assertEquals('0 0 * * *', $this->command->getDefaultDefinition());
    }

    public function testIsActiveWithSequences(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('hasSequences')
            ->willReturn(true);

        self::assertTrue($this->command->isActive());
    }

    public function testIsActiveWithoutSequences(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('hasSequences')
            ->willReturn(false);

        self::assertFalse($this->command->isActive());
    }

    public function testExecuteWithNoSequenceTypes(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->repository
            ->expects(self::once())
            ->method('findUniqueSequenceTypes')
            ->willReturn([]);

        $this->messageProducer
            ->expects(self::never())
            ->method('send');

        $result = $this->command->execute($input, $output);
        self::assertEquals(0, $result);
    }

    public function testExecuteWithSequenceTypes(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $sequenceTypes = [
            ['sequenceType' => 'type1', 'discriminatorType' => 'discr1'],
            ['sequenceType' => 'type2', 'discriminatorType' => 'discr2'],
        ];

        $this->repository
            ->expects(self::once())
            ->method('findUniqueSequenceTypes')
            ->willReturn($sequenceTypes);

        $this->messageProducer
            ->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    DeleteOldNumberSequenceTopic::getName(),
                    ['sequenceType' => 'type1', 'discriminatorType' => 'discr1']
                ],
                [
                    DeleteOldNumberSequenceTopic::getName(),
                    ['sequenceType' => 'type2', 'discriminatorType' => 'discr2']
                ]
            );

        $result = $this->command->execute($input, $output);
        self::assertEquals(0, $result);
    }

    public function testExecuteWithException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->repository
            ->expects(self::once())
            ->method('findUniqueSequenceTypes')
            ->willThrowException(new \RuntimeException('Database error'));

        $this->messageProducer
            ->expects(self::never())
            ->method('send');

        $this->command->execute($input, $output);
    }
}
