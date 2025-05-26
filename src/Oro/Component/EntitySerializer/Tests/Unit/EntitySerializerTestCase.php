<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\ConfigConverter;
use Oro\Component\EntitySerializer\ConfigNormalizer;
use Oro\Component\EntitySerializer\DataNormalizer;
use Oro\Component\EntitySerializer\DataTransformer;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\EntityDataAccessor;
use Oro\Component\EntitySerializer\EntityFieldFilterInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Component\EntitySerializer\FieldAccessor;
use Oro\Component\EntitySerializer\FieldFilterInterface;
use Oro\Component\EntitySerializer\QueryFactory;
use Oro\Component\EntitySerializer\QueryResolver;
use Oro\Component\EntitySerializer\SerializationHelper;
use Oro\Component\Testing\Unit\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class EntitySerializerTestCase extends OrmTestCase
{
    protected EntityManagerInterface $em;
    protected EntityFieldFilterInterface&MockObject $entityFieldFilter;
    protected ContainerInterface&MockObject $container;
    protected EntitySerializer $serializer;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
        $this->em->getConfiguration()->setMetadataDriverImpl(new AttributeDriver([]));

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->entityFieldFilter = $this->createMock(EntityFieldFilterInterface::class);
        $this->entityFieldFilter->expects(self::any())
            ->method('isApplicableField')
            ->willReturn(true);

        $queryHintResolver = $this->createMock(QueryHintResolverInterface::class);
        $queryHintResolver->expects(self::any())
            ->method('resolveHints')
            ->willReturnCallback(function (Query $query, array $hints = []) {
                if (!empty($hints)) {
                    foreach ($hints as $hint) {
                        if (is_array($hint)) {
                            $query->setHint($hint['name'], $hint['value']);
                        } elseif (is_string($hint)) {
                            $query->setHint($hint, true);
                        }
                    }
                }
            });

        $this->container = $this->createMock(ContainerInterface::class);
        $doctrineHelper = new DoctrineHelper($doctrine);
        $dataAccessor = new EntityDataAccessor();
        $fieldAccessor = new FieldAccessor($doctrineHelper, $dataAccessor, $this->entityFieldFilter);
        $this->serializer = new EntitySerializer(
            $doctrineHelper,
            new SerializationHelper(new DataTransformer($this->container)),
            $dataAccessor,
            new QueryFactory($doctrineHelper, new QueryResolver($queryHintResolver)),
            $fieldAccessor,
            new ConfigNormalizer(),
            new ConfigConverter(),
            new DataNormalizer()
        );
    }

    protected function assertArrayEquals(array $expected, array $actual, string $message = ''): void
    {
        $this->sortByKeyRecursive($expected);
        $this->sortByKeyRecursive($actual);
        self::assertSame($expected, $actual, $message);
    }

    protected function assertDqlEquals(string $expected, string $actual, string $message = ''): void
    {
        self::assertEquals($expected, $actual, $message);
    }

    protected function sortByKeyRecursive(array &$array): void
    {
        ksort($array);
        foreach ($array as &$val) {
            if ($val && is_array($val)) {
                $this->sortByKeyRecursive($val);
            }
        }
    }

    protected function getFieldFilter(array $checkRules): FieldFilterInterface
    {
        $filter = $this->createMock(FieldFilterInterface::class);
        $filter->expects(self::any())
            ->method('checkField')
            ->willReturnCallback(function ($entity, $entityClass, $field) use ($checkRules) {
                return $checkRules[$field] ?? null;
            });

        return $filter;
    }
}
