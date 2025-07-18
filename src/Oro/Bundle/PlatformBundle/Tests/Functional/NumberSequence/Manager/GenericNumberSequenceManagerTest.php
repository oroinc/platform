<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Functional\NumberSequence\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\NumberSequence\Manager\GenericNumberSequenceManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class GenericNumberSequenceManagerTest extends WebTestCase
{
    private const string SEQUENCE_TYPE = 'test_sequence';
    private const string DISCRIMINATOR_TYPE = 'test_discriminator';
    private const string DISCRIMINATOR_VALUE = 'test_value';

    private GenericNumberSequenceManager $manager;
    private EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(NumberSequence::class);
        $this->manager = new GenericNumberSequenceManager(
            self::getContainer()->get('doctrine'),
            self::SEQUENCE_TYPE,
            self::DISCRIMINATOR_TYPE,
            self::DISCRIMINATOR_VALUE
        );
    }

    public function testNextNumberIncrementsSequence(): void
    {
        self::assertEquals(1, $this->manager->nextNumber());
        self::assertEquals(2, $this->manager->nextNumber());

        $sequence = $this->getSequence();
        self::assertNotNull($sequence);
        self::assertEquals(2, $sequence->getNumber());
    }

    public function testNextNumberCreatesSequenceIfNotExists(): void
    {
        self::assertNull($this->getSequence());

        $number = $this->manager->nextNumber();

        self::assertEquals(1, $number);

        $sequence = $this->getSequence();
        self::assertNotNull($sequence);
        self::assertEquals(self::SEQUENCE_TYPE, $sequence->getSequenceType());
        self::assertEquals(self::DISCRIMINATOR_TYPE, $sequence->getDiscriminatorType());
        self::assertEquals(self::DISCRIMINATOR_VALUE, $sequence->getDiscriminatorValue());
        self::assertEquals(1, $sequence->getNumber());
    }

    public function testResetSequenceSetsNumber(): void
    {
        $this->manager->nextNumber();

        $this->manager->resetSequence(5);

        $sequence = $this->getSequence();
        self::assertNotNull($sequence);
        self::assertEquals(5, $sequence->getNumber());
        self::assertEquals(6, $this->manager->nextNumber());
    }

    public function testResetSequenceToZero(): void
    {
        $this->manager->nextNumber();

        $this->manager->resetSequence(0);

        $sequence = $this->getSequence();
        self::assertNotNull($sequence);
        self::assertEquals(0, $sequence->getNumber());
        self::assertEquals(1, $this->manager->nextNumber());
    }

    public function testResetSequenceCreatesSequenceIfNotExists(): void
    {
        self::assertNull($this->getSequence());

        $this->manager->resetSequence(10);

        $sequence = $this->getSequence();
        self::assertNotNull($sequence);
        self::assertEquals(10, $sequence->getNumber());
    }

    public function testResetSequenceWithNegativeNumberThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sequence number must be a positive integer.');

        $this->manager->resetSequence(-123);
    }

    public function testReserveSequenceReturnsRange(): void
    {
        $reservedNumbers = $this->manager->reserveSequence(3);

        self::assertEquals([1, 2, 3], $reservedNumbers);

        $sequence = $this->getSequence();
        self::assertNotNull($sequence);
        self::assertEquals(3, $sequence->getNumber());
        self::assertEquals(4, $this->manager->nextNumber());
    }

    public function testReserveSequenceFromExistingNumber(): void
    {
        $this->manager->resetSequence(5);

        $reservedNumbers = $this->manager->reserveSequence(3);

        self::assertEquals([6, 7, 8], $reservedNumbers);

        $sequence = $this->getSequence();
        self::assertEquals(8, $sequence->getNumber());
    }

    public function testReserveSequenceSingleNumber(): void
    {
        $reservedNumbers = $this->manager->reserveSequence(1);

        self::assertEquals([1], $reservedNumbers);

        $sequence = $this->getSequence();
        self::assertEquals(1, $sequence->getNumber());
    }

    public function testReserveSequenceWithZeroSizeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Size must be a positive integer.');

        $this->manager->reserveSequence(0);
    }

    public function testReserveSequenceWithNegativeSizeThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Size must be a positive integer.');

        $this->manager->reserveSequence(-55);
    }

    public function testSequentialOperationsMaintainConsistency(): void
    {
        $num1 = $this->manager->nextNumber();
        self::assertEquals(1, $num1);

        $reserved = $this->manager->reserveSequence(3);
        self::assertEquals([2, 3, 4], $reserved);

        $num2 = $this->manager->nextNumber();
        self::assertEquals(5, $num2);

        $this->manager->resetSequence(10);

        $num3 = $this->manager->nextNumber();
        self::assertEquals(11, $num3);
    }

    public function testDifferentDiscriminatorsAreIsolated(): void
    {
        $manager1 = new GenericNumberSequenceManager(
            self::getContainer()->get('doctrine'),
            self::SEQUENCE_TYPE,
            self::DISCRIMINATOR_TYPE,
            'value1'
        );

        $manager2 = new GenericNumberSequenceManager(
            self::getContainer()->get('doctrine'),
            self::SEQUENCE_TYPE,
            self::DISCRIMINATOR_TYPE,
            'value2'
        );

        self::assertEquals(1, $manager1->nextNumber());
        self::assertEquals(1, $manager2->nextNumber());
        self::assertEquals(2, $manager1->nextNumber());
        self::assertEquals(2, $manager2->nextNumber());

        $sequence1 = $this->getSequenceByDiscriminator('value1');
        $sequence2 = $this->getSequenceByDiscriminator('value2');

        self::assertNotNull($sequence1);
        self::assertNotNull($sequence2);
        self::assertEquals(2, $sequence1->getNumber());
        self::assertEquals(2, $sequence2->getNumber());
    }

    private function getSequence(): ?NumberSequence
    {
        return $this->entityManager->getRepository(NumberSequence::class)->findOneBy([
            'sequenceType' => self::SEQUENCE_TYPE,
            'discriminatorType' => self::DISCRIMINATOR_TYPE,
            'discriminatorValue' => self::DISCRIMINATOR_VALUE,
        ]);
    }

    private function getSequenceByDiscriminator(string $discriminatorValue): ?NumberSequence
    {
        return $this->entityManager->getRepository(NumberSequence::class)->findOneBy([
            'sequenceType' => self::SEQUENCE_TYPE,
            'discriminatorType' => self::DISCRIMINATOR_TYPE,
            'discriminatorValue' => $discriminatorValue,
        ]);
    }
}
