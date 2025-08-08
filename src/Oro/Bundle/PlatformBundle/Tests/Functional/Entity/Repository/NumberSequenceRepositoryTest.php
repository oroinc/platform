<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Entity\Repository\NumberSequenceRepository;
use Oro\Bundle\PlatformBundle\Tests\Functional\DataFixtures\LoadNumberSequences;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class NumberSequenceRepositoryTest extends WebTestCase
{
    private NumberSequenceRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadNumberSequences::class]);
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository = $entityManager->getRepository(NumberSequence::class);
    }

    public function testHasSequences(): void
    {
        self::assertTrue($this->repository->hasSequences());
    }

    public function testHasNoSequences(): void
    {
        self::getContainer()->get('doctrine')->getConnection()->executeQuery('DELETE FROM oro_number_sequence');
        self::assertFalse($this->repository->hasSequences());
    }

    public function testFindUniqueSequenceTypes(): void
    {
        $result = $this->repository->findUniqueSequenceTypes();

        self::assertEqualsCanonicalizing(
            [
                ['sequenceType' => 'invoice', 'discriminatorType' => 'organization_periodic'],
                ['sequenceType' => 'invoice', 'discriminatorType' => 'regular'],
                ['sequenceType' => 'order', 'discriminatorType' => 'regular'],
            ],
            $result
        );
    }

    public function testIncrementSequenceCreatesNew(): void
    {
        $incrementSequence = $this->repository->incrementSequence(
            'test_sequence',
            'test_discriminator',
            'test_value',
            5
        );

        self::assertInstanceOf(NumberSequence::class, $incrementSequence);
        self::assertEquals('test_sequence', $incrementSequence->getSequenceType());
        self::assertEquals('test_discriminator', $incrementSequence->getDiscriminatorType());
        self::assertEquals('test_value', $incrementSequence->getDiscriminatorValue());
        self::assertEquals(5, $incrementSequence->getNumber());
        self::assertNotNull($incrementSequence->getId());
        self::assertInstanceOf(\DateTime::class, $incrementSequence->getCreatedAt());
        self::assertInstanceOf(\DateTime::class, $incrementSequence->getUpdatedAt());
    }

    public function testIncrementSequenceIncrementsExisting(): void
    {
        // Create initial sequence
        $firstSequence = $this->repository->incrementSequence(
            'test_sequence',
            'test_discriminator',
            'test_value',
            10
        );

        // Increment existing sequence
        $secondSequence = $this->repository->incrementSequence(
            'test_sequence',
            'test_discriminator',
            'test_value',
            3
        );

        self::assertEquals($firstSequence->getId(), $secondSequence->getId());
        self::assertEquals(13, $secondSequence->getNumber());
        self::assertEquals($firstSequence->getCreatedAt(), $secondSequence->getCreatedAt());
        self::assertGreaterThanOrEqual($firstSequence->getUpdatedAt(), $secondSequence->getUpdatedAt());
    }

    public function testIncrementSequenceDefaultIncrement(): void
    {
        $firstSequence = $this->repository->incrementSequence(
            'test_sequence',
            'test_discriminator',
            'test_value'
        );

        self::assertEquals(1, $firstSequence->getNumber());

        $secondSequence = $this->repository->incrementSequence(
            'test_sequence',
            'test_discriminator',
            'test_value'
        );

        self::assertEquals(2, $secondSequence->getNumber());
    }

    public function testResetSequenceCreatesNew(): void
    {
        $result = $this->repository->resetSequence(
            'reset_sequence',
            'reset_discriminator',
            'reset_value',
            100
        );

        self::assertEquals('reset_sequence', $result->getSequenceType());
        self::assertEquals('reset_discriminator', $result->getDiscriminatorType());
        self::assertEquals('reset_value', $result->getDiscriminatorValue());
        self::assertEquals(100, $result->getNumber());
        self::assertNotNull($result->getId());
    }

    public function testResetSequenceResetsExisting(): void
    {
        // Create sequence with high number
        $initialSequence = $this->repository->incrementSequence(
            'reset_sequence',
            'reset_discriminator',
            'reset_value',
            50
        );

        // Reset to lower number
        $resetSequence = $this->repository->resetSequence(
            'reset_sequence',
            'reset_discriminator',
            'reset_value',
            10
        );

        self::assertEquals($initialSequence->getId(), $resetSequence->getId());
        self::assertEquals(10, $resetSequence->getNumber());
        self::assertEquals($initialSequence->getCreatedAt(), $resetSequence->getCreatedAt());
        self::assertGreaterThanOrEqual($initialSequence->getUpdatedAt(), $resetSequence->getUpdatedAt());
    }

    public function testResetSequenceDefaultValue(): void
    {
        // Create sequence
        $initialSequence = $this->repository->incrementSequence(
            'reset_sequence',
            'reset_discriminator',
            'reset_value',
            25
        );

        // Reset to default (0)
        $resetSequence = $this->repository->resetSequence(
            'reset_sequence',
            'reset_discriminator',
            'reset_value'
        );

        self::assertEquals($initialSequence->getId(), $resetSequence->getId());
        self::assertEquals(0, $resetSequence->getNumber());
    }

    public function testFindByTypeAndDiscriminatorOrdered(): void
    {
        // Create multiple sequences with same type/discriminator
        $this->repository->incrementSequence('order_test', 'test_disc', 'value1', 10);
        $this->repository->incrementSequence('order_test', 'test_disc', 'value2', 20);
        $this->repository->incrementSequence('order_test', 'other_disc', 'value3', 30);

        $results = $this->repository
            ->findByTypeAndDiscriminatorOrdered('order_test', 'test_disc');

        self::assertCount(2, $results);
        self::assertEquals('order_test', $results[0]->getSequenceType());
        self::assertEquals('test_disc', $results[0]->getDiscriminatorType());
        // Should be ordered by id DESC by default
        self::assertGreaterThan($results[1]->getId(), $results[0]->getId());
    }

    public function testFindByTypeAndDiscriminatorOrderedCustomOrder(): void
    {
        $this->repository->incrementSequence('custom_order', 'test', 'val1', 100);
        $this->repository->incrementSequence('custom_order', 'test', 'val2', 50);

        $results = $this->repository
            ->findByTypeAndDiscriminatorOrdered('custom_order', 'test', ['number' => 'ASC']);

        self::assertCount(2, $results);
        self::assertEquals(50, $results[0]->getNumber());
        self::assertEquals(100, $results[1]->getNumber());
    }
}
