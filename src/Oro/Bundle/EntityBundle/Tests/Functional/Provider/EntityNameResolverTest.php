<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Provider;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverClassesProviderInterface;
use Oro\Bundle\EntityBundle\Tests\Functional\Environment\TestEntityNameResolverDataLoaderInterface;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\TranslationBundle\Entity\Language;

class EntityNameResolverTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        self::markTestSkipped(
            'Skipped during to memory usage in this test exceeds 1GB, waiting fixes in ticket/BAP-22701'
        );
        $this->initClient();
        $this->loadFixtures([LoadOrganization::class, LoadBusinessUnit::class, LoadUser::class]);
    }

    private function getEntityClassesProvider(): TestEntityNameResolverClassesProviderInterface
    {
        return self::getContainer()->get('oro_entity.tests.entity_name_resolver.classes_provider');
    }

    private function getEntityDataLoader(): TestEntityNameResolverDataLoaderInterface
    {
        return self::getContainer()->get('oro_entity.tests.entity_name_resolver.data_loader');
    }

    private function getDoctrine(): ManagerRegistry
    {
        return self::getContainer()->get('doctrine');
    }

    private function getEntityNameResolver(): EntityNameResolver
    {
        return self::getContainer()->get('oro_entity.entity_name_resolver');
    }

    private function getLoadNameQuery(
        EntityManagerInterface $em,
        string $entityClass,
        object $entity,
        string $dql
    ): QueryBuilder {
        $metadata = $em->getClassMetadata($entityClass);
        $idFieldName = $metadata->getIdentifierFieldNames()[0];
        $id = $metadata->getIdentifierValues($entity)[$idFieldName];

        return $em->createQueryBuilder()
            ->from($entityClass, 'e')
            ->select($dql . ' AS name')
            ->where('e.' . $idFieldName . ' = :id')
            ->setParameter('id', $id);
    }

    private function getLocalization(string $locale): Localization
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManagerForClass(Localization::class);
        $language = $em->getRepository(Language::class)->findOneBy(['code' => $locale]);
        if (null === $language) {
            $language = new Language();
            $language->setCode($locale);
            $language->setEnabled(1);
            $em->persist($language);

            $localization = new Localization();
            $localization->setLanguage($language);
            $localization->setFormattingCode($locale);
            $localization->setName('Localization ' . $locale);
            $em->persist($localization);
            $em->flush();
        } else {
            $localization = $em->getRepository(Localization::class)->findOneBy(['language' => $language]);
            if (null === $localization) {
                $localization = new Localization();
                $localization->setLanguage($language);
                $localization->setFormattingCode($locale);
                $localization->setName($locale);
                $em->persist($localization);
                $em->flush();
            }
        }

        return $localization;
    }

    public function testEntityNameResolver(): void
    {
        $errors = [];
        $formats = [null, EntityNameProviderInterface::FULL, EntityNameProviderInterface::SHORT];
        $deLocalization = $this->getLocalization('de_DE');
        $frLocalization = $this->getLocalization('fr_FR');
        $locales = [null, 'de_DE', $deLocalization];
        $entityNameResolver = $this->getEntityNameResolver();
        $entityDataLoader = $this->getEntityDataLoader();
        $doctrine = $this->getDoctrine();
        $entityClasses = $this->getEntityClassesProvider()->getEntityClasses();
        ksort($entityClasses);
        foreach ($entityClasses as $entityClass => $reasons) {
            /** @var EntityManagerInterface $em */
            $em = $doctrine->getManagerForClass($entityClass);
            $referenceRepository = new ReferenceRepository($em);
            $referenceRepository->addReference('organization', $this->getReference(LoadOrganization::ORGANIZATION));
            $referenceRepository->addReference('business_unit', $this->getReference(LoadBusinessUnit::BUSINESS_UNIT));
            $referenceRepository->addReference('user', $this->getReference(LoadUser::USER));
            $referenceRepository->addReference('de_DE', $deLocalization);
            $referenceRepository->addReference('fr_FR', $frLocalization);
            try {
                $entityReferences = $entityDataLoader->loadEntity($em, $referenceRepository, $entityClass);
            } catch (\Throwable $e) {
                $errors[] = sprintf('Entity: %s. Failed to load entity data. %s', $entityClass, $e->getMessage());
                continue;
            }
            if (!$entityReferences) {
                sort($reasons);
                $errors[] = sprintf(
                    'A test data loader for the "%s" entity was not found.'
                    . ' The reason(s) why a test data loader is needed: "%s".',
                    $entityClass,
                    implode('", "', $reasons)
                );
                continue;
            }
            foreach ($entityReferences as $entityReference) {
                $this->validateEntityName(
                    $entityNameResolver,
                    $entityDataLoader,
                    $referenceRepository,
                    $em,
                    $entityClass,
                    $entityReference,
                    $formats,
                    $locales,
                    $errors
                );
            }
        }

        if ($errors) {
            self::fail(
                "Found the following issues:\n\n  - "
                . implode("\n  - ", $errors)
                . "\n\nNOTE:\n"
                . '  When you see "A test data loader for the ... entity was not found."'
                . ' message then you have several solutions:'
                . "\n     1. when this entity is searchable or associated with auditable entities than you must"
                . ' implement the test data loader to be sure that the entity has a correct text representation'
                . "\n     2. when this entity is exposed via API and it is not searchable and not data audit related"
                . ' than depending on the entity type you can implement the test data loader'
                . ' or add "disable_meta_properties: [ title ]" or "disable_meta_properties: true"'
                . ' to "api.yml" if the entity do not need'
                . ' a text representation'
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function validateEntityName(
        EntityNameResolver $entityNameResolver,
        TestEntityNameResolverDataLoaderInterface $entityDataLoader,
        ReferenceRepository $referenceRepository,
        EntityManagerInterface $em,
        string $entityClass,
        string $entityReference,
        array $formats,
        array $locales,
        array &$errors
    ): void {
        $entity = $referenceRepository->getReference($entityReference);
        foreach ($formats as $format) {
            foreach ($locales as $locale) {
                $localeName = $locale instanceof Localization ? $locale->getName() : $locale;
                try {
                    $expectedEntityName = $entityDataLoader->getExpectedEntityName(
                        $referenceRepository,
                        $entityClass,
                        $entityReference,
                        $format,
                        $localeName
                    );
                } catch (\LogicException $e) {
                    $errors[] = $e->getMessage();
                    continue;
                }
                $entityName = $entityNameResolver->getName($entity, $format, $locale);
                if ($entityName !== $expectedEntityName) {
                    $errors[] = sprintf(
                        'Entity: %s. Entity reference: %s. Format: %s. Locale: %s.'
                        . ' Failed asserting that %s returned by'
                        . ' EntityNameResolver::getName() is equal to "%s".',
                        $entityClass,
                        $entityReference,
                        $format,
                        $localeName,
                        null === $entityName ? 'null' : '"' . $entityName . '"',
                        $expectedEntityName
                    );
                }
                $dql = $entityNameResolver->getNameDQL($entityClass, 'e', $format, $locale);
                if (null === $dql) {
                    $errors[] = sprintf(
                        'Entity: %s. Entity reference: %s. Format: %s. Locale: %s.'
                        . ' The DQL expression returned by EntityNameResolver::getNameDQL() is null.',
                        $entityClass,
                        $entityReference,
                        $format,
                        $localeName
                    );
                    continue;
                }
                try {
                    $rows = $this->getLoadNameQuery($em, $entityClass, $entity, $dql)
                        ->getQuery()
                        ->getArrayResult();
                } catch (\Exception $e) {
                    $errors[] = sprintf(
                        'Entity: %s. Entity reference: %s. Format: %s. Locale: %s. Failed to load entity name'
                        . ' by the DQL expression returned by EntityNameResolver::getNameDQL(). %s',
                        $entityClass,
                        $entityReference,
                        $format,
                        $localeName,
                        $e->getMessage()
                    );
                    continue;
                }
                if (!$rows) {
                    $errors[] = sprintf(
                        'Entity: %s. Entity reference: %s. Format: %s. Locale: %s.'
                        . ' The entity does not exist in the database.',
                        $entityClass,
                        $entityReference,
                        $format,
                        $localeName
                    );
                    continue;
                }
                $entityName = $rows[0]['name'];
                if ($entityName !== $expectedEntityName) {
                    $errors[] = sprintf(
                        'Entity: %s. Entity reference: %s. Format: %s. Locale: %s.'
                        . ' Failed asserting that %s loaded by'
                        . ' DQL expression returned by EntityNameResolver::getNameDQL() is equal to "%s".',
                        $entityClass,
                        $entityReference,
                        $format,
                        $localeName,
                        null === $entityName ? 'null' : '"' . $entityName . '"',
                        $expectedEntityName
                    );
                }
            }
        }
    }
}
