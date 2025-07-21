<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Entity\Repository\NumberSequenceRepository;
use Oro\Bundle\PlatformBundle\Tests\Functional\DataFixtures\LoadNumberSequences;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class NumberSequenceRepositoryTest extends WebTestCase
{
    private NumberSequenceRepository $repository;
    private EntityManagerInterface $em;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadNumberSequences::class]);
        $this->em = self::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->em->getRepository(NumberSequence::class);
    }

    public function testGetLockedSequence(): void
    {
        $sequence = new NumberSequence();
        $sequence->setSequenceType('invoice')
            ->setDiscriminatorType('organization_periodic')
            ->setDiscriminatorValue('1:2024-03')
            ->setNumber(1);

        $this->em->persist($sequence);
        $this->em->flush();

        $result = $this->repository->getLockedSequence(
            'invoice',
            'organization_periodic',
            '1:2024-03'
        );

        self::assertNotNull($result);
        self::assertInstanceOf(NumberSequence::class, $result);
        self::assertEquals('invoice', $result->getSequenceType());
        self::assertEquals('organization_periodic', $result->getDiscriminatorType());
        self::assertEquals('1:2024-03', $result->getDiscriminatorValue());
        self::assertEquals(1, $result->getNumber());
    }

    public function testGetLockedSequenceReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->getLockedSequence(
            'non_existing_type',
            'non_existing_discriminator',
            'non_existing_value'
        );

        self::assertNull($result);
    }

    public function testGetLockedSequenceWithMultipleSequences(): void
    {
        $sequence1 = new NumberSequence();
        $sequence1->setSequenceType('invoice')
            ->setDiscriminatorType('organization_periodic')
            ->setDiscriminatorValue('1:2024-03')
            ->setNumber(1);

        $sequence2 = new NumberSequence();
        $sequence2->setSequenceType('invoice')
            ->setDiscriminatorType('organization_periodic')
            ->setDiscriminatorValue('2:2024-03')
            ->setNumber(2);

        $sequence3 = new NumberSequence();
        $sequence3->setSequenceType('invoice')
            ->setDiscriminatorType('organization_periodic')
            ->setDiscriminatorValue('3:2024-03')
            ->setNumber(3);

        $this->em->persist($sequence1);
        $this->em->persist($sequence2);
        $this->em->persist($sequence3);
        $this->em->flush();

        $result = $this->repository->getLockedSequence(
            'invoice',
            'organization_periodic',
            '2:2024-03'
        );

        self::assertNotNull($result);
        self::assertEquals(2, $result->getNumber());
        self::assertEquals('2:2024-03', $result->getDiscriminatorValue());
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
}
