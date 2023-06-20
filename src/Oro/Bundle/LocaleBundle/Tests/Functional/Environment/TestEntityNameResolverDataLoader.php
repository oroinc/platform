<?php

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Environment;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Symfony\Contracts\Translation\TranslatorInterface;

class TestEntityNameResolverDataLoader implements TestEntityNameResolverDataLoaderInterface
{
    private TestEntityNameResolverDataLoaderInterface $innerDataLoader;
    private ManagerRegistry $doctrine;
    private TranslatorInterface $translator;

    public function __construct(
        TestEntityNameResolverDataLoaderInterface $innerDataLoader,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator
    ) {
        $this->innerDataLoader = $innerDataLoader;
        $this->doctrine = $doctrine;
        $this->translator = $translator;
    }

    public function loadEntity(
        EntityManagerInterface $em,
        ReferenceRepository $repository,
        string $entityClass
    ): array {
        if (Localization::class === $entityClass) {
            return ['de_DE'];
        }

        if (is_a($entityClass, AbstractLocalizedFallbackValue::class, true)) {
            $shortClassName = substr($entityClass, strrpos($entityClass, '\\') + 1);
            $hasStringField = $this->getEntityMetadata($entityClass)->hasField('string');

            $value = new $entityClass();
            $value->setLocalization($repository->getReference('de_DE'));
            if ($hasStringField) {
                $value->setString('Test Localized Fallback Value');
            } else {
                $value->setText('Test Localized Fallback Value');
            }
            $valueReference = 'localizedFallbackValue_' . $shortClassName;
            $repository->setReference($valueReference, $value);
            $em->persist($value);

            $valueWithoutLocalization = new $entityClass();
            if ($hasStringField) {
                $valueWithoutLocalization->setString('Test Localized Fallback Value');
            } else {
                $valueWithoutLocalization->setText('Test Localized Fallback Value');
            }
            $valueWithoutLocalizationReference = 'localizedFallbackValueWithoutLocalization_' . $shortClassName;
            $repository->setReference($valueWithoutLocalizationReference, $valueWithoutLocalization);
            $em->persist($valueWithoutLocalization);

            $em->flush();

            return [$valueReference, $valueWithoutLocalizationReference];
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
        if (Localization::class === $entityClass) {
            return 'Localization de_DE';
        }
        if (is_a($entityClass, AbstractLocalizedFallbackValue::class, true)) {
            return str_starts_with($entityReference, 'localizedFallbackValue_')
                ? 'Localization de_DE'
                : $this->translator->trans(
                    'oro.locale.fallback.value.default',
                    [],
                    null,
                    $locale && str_starts_with($locale, 'Localization ')
                        ? substr($locale, \strlen('Localization '))
                        : $locale
                );
        }

        return $this->innerDataLoader->getExpectedEntityName(
            $repository,
            $entityClass,
            $entityReference,
            $format,
            $locale
        );
    }

    private function getEntityMetadata(string $entityClass): ClassMetadata
    {
        return $this->doctrine->getManagerForClass($entityClass)->getClassMetadata($entityClass);
    }
}
