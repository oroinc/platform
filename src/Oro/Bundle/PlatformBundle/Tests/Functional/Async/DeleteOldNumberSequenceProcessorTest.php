<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Functional\Async;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\PlatformBundle\Async\Topic\DeleteOldNumberSequenceTopic;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Tests\Functional\DataFixtures\LoadNumberSequences;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

final class DeleteOldNumberSequenceProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    private const string SEQUENCE_TYPE = 'order';
    private const string DISCRIMINATOR_TYPE = 'regular';
    private const string PROCESSOR_ID = 'oro_platform.async.delete_old_number_sequence_processor';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadNumberSequences::class]);
    }

    public function testProcess(): void
    {
        $message = self::sendMessage(
            DeleteOldNumberSequenceTopic::getName(),
            [
                'sequenceType' => self::SEQUENCE_TYPE,
                'discriminatorType' => self::DISCRIMINATOR_TYPE
            ]
        );

        $repository = self::getContainer()
            ->get('doctrine')
            ->getRepository(NumberSequence::class);

        $initialSequences = $repository->findBy([
            'sequenceType' => self::SEQUENCE_TYPE,
            'discriminatorType' => self::DISCRIMINATOR_TYPE
        ]);
        self::assertNotEmpty($initialSequences);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);
        self::assertProcessedMessageProcessor(self::PROCESSOR_ID, $message);

        $remainingSequences = $repository->findBy([
            'sequenceType' => self::SEQUENCE_TYPE,
            'discriminatorType' => self::DISCRIMINATOR_TYPE
        ]);
        self::assertCount(count($initialSequences), $remainingSequences);
    }

    public function testProcessRejectsInvalidMessage(): void
    {
        $message = self::sendMessage(
            DeleteOldNumberSequenceTopic::getName(),
            [
                'sequenceType' => self::SEQUENCE_TYPE
            ]
        );

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);
        self::assertProcessedMessageProcessor(self::PROCESSOR_ID, $message);
    }
}
