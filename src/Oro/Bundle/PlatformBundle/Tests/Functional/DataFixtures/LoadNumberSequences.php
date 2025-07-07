<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;

class LoadNumberSequences extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->createNumberSequence($manager, 'invoice', 'organization_periodic', 1);
        $this->createNumberSequence($manager, 'invoice', 'organization_periodic', 2);
        $this->createNumberSequence($manager, 'invoice', 'organization_periodic', 3);
        $this->createNumberSequence($manager, 'invoice', 'regular', 4);
        $this->createNumberSequence($manager, 'order', 'regular', 5);

        $manager->flush();
    }

    private function createNumberSequence(
        ObjectManager $manager,
        string $sequenceType,
        string $discriminatorType,
        int $number
    ): void {
        $sequence = new NumberSequence();
        $sequence->setSequenceType($sequenceType);
        $sequence->setDiscriminatorType($discriminatorType);
        $sequence->setDiscriminatorValue($sequenceType . '-' . $discriminatorType . '-' . $number);
        $sequence->setNumber($number);

        $manager->persist($sequence);
        $this->addReference(sprintf('number_sequence.%s.%s.%d', $sequenceType, $discriminatorType, $number), $sequence);
    }
}
