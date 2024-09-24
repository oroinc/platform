<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    #[\Override]
    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Item::class === $entityClass) {
            $item = new Item();
            $item->organization = $repository->getReference('organization');
            $item->owner = $repository->getReference('user');
            $item->stringValue = 'string';
            $item->integerValue = 123;
            $item->decimalValue = 123.45;
            $item->floatValue = 234.56;
            $item->booleanValue = true;
            $item->blobValue = 'blob';
            $item->arrayValue = [1, 2, 3];
            $item->datetimeValue = new \DateTime('now');
            $item->guidValue = UUIDGenerator::v4();
            $item->phone = '123-123';
            $repository->setReference('testItem', $item);
            $em->persist($item);
            $em->flush();

            return ['testItem'];
        }

        return [];
    }

    #[\Override]
    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (Item::class === $entityClass) {
            return EntityNameProviderInterface::SHORT === $format
                ? $repository->getReference($entityReference)->getId()
                : 'string 123-123';
        }

        throw new \LogicException(sprintf(
            'An expected entity name for the entity "%s" (reference: %s) was not provided.',
            $entityClass,
            $entityReference
        ));
    }
}
