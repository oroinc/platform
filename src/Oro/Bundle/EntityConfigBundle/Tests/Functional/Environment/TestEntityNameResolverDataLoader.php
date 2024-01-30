<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\UserBundle\Entity\User;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;

    public function __construct(TestEntityNameResolverDataLoaderInterface $innerDataLoader)
    {
        $this->innerDataLoader = $innerDataLoader;
    }

    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (AttributeFamily::class === $entityClass) {
            $family = new AttributeFamily();
            $family->setEntityClass(User::class);
            $family->setCode('TEST_ATTRIBUTE_FAMILY');
            $family->addLabel($this->createLocalizedFallbackValue($em, 'Test Attribute Family'));
            $family->addLabel($this->createLocalizedFallbackValue(
                $em,
                'Test Attribute Family (de_DE)',
                $repository->getReference('de_DE')
            ));
            $family->addLabel($this->createLocalizedFallbackValue(
                $em,
                'Test Attribute Family (fr_FR)',
                $repository->getReference('fr_FR')
            ));
            $repository->setReference('attributeFamily', $family);
            $em->persist($family);

            $familyWithEmptyName = new AttributeFamily();
            $familyWithEmptyName->setEntityClass(User::class);
            $familyWithEmptyName->setCode('TEST_ATTRIBUTE_FAMILY_1');
            $familyWithEmptyName->addLabel($this->createLocalizedFallbackValue($em, ''));
            $repository->setReference('attributeFamilyWithEmptyName', $familyWithEmptyName);
            $em->persist($familyWithEmptyName);

            $em->flush();

            return ['attributeFamily', 'attributeFamilyWithEmptyName'];
        }

        return $this->innerDataLoader->loadEntity($em, $repository, $entityClass);
    }

    public function getExpectedEntityName(
        ReferenceRepository $repository,
        string $entityClass,
        string $entityReference,
        ?string $format,
        ?string $locale
    ): string {
        if (AttributeFamily::class === $entityClass) {
            if ('attributeFamilyWithEmptyName' === $entityReference) {
                return 'TEST_ATTRIBUTE_FAMILY_1';
            }

            if ('attributeFamily' === $entityReference && EntityNameProviderInterface::SHORT === $format) {
                return 'TEST_ATTRIBUTE_FAMILY';
            }

            if ('attributeFamily' === $entityReference) {
                return 'Localization de_DE' === $locale
                    ? 'Test Attribute Family (de_DE)'
                    : 'Test Attribute Family';
            }
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }

    private function createLocalizedFallbackValue(
        EntityManagerInterface $em,
        string $value,
        ?Localization $localization = null
    ): LocalizedFallbackValue {
        $lfv = new LocalizedFallbackValue();
        $lfv->setString($value);
        if (null !== $localization) {
            $lfv->setLocalization($localization);
        }
        $em->persist($lfv);

        return $lfv;
    }
}
