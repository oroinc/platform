<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Provides approximate tuple length (number of columns in a row) for the export query of the given entity class.
 */
class ExportQueryTupleLengthProvider
{
    private ManagerRegistry $doctrine;
    private array $tupleLengthByClassName;
    private ExportQueryProvider $exportQueryProvider;

    public function __construct(ManagerRegistry $doctrine, ExportQueryProvider $exportQueryProvider)
    {
        $this->doctrine = $doctrine;
        $this->exportQueryProvider = $exportQueryProvider;
    }

    /**
     * Provides approximate tuple length by summing the count of scalar field names of the main entity + relations ids
     * with single join column + scalar fields of joined relations + relations ids of joined relations with single join
     * column. The resulting length is actual for default Oro\Bundle\ImportExportBundle\Reader\EntityReader which is
     * usually used for export.
     *
     * The real query from EntityReader source is not analyzed for columns number as it takes too much time
     * for doctrine to parse it. For example, for Product entity with ~500 enum attributes (which results in ~2661
     * columns in export query), the difference is ~100 times - 0.5sec when parsing a doctrine query and 0.005sec when
     * manually summing possible columns in this method.
     */
    public function getTupleLength(string $className, bool $forceRecalculate = true): int
    {
        if ($forceRecalculate || !isset($this->tupleLengthByClassName[$className])) {
            /** @var EntityManager $entityManager */
            $entityManager = $this->doctrine->getManagerForClass($className);

            $metadata = $entityManager->getClassMetadata($className);

            // For main entity fields.
            $this->tupleLengthByClassName[$className] = count($metadata->getFieldNames());
            foreach ($metadata->getAssociationNames() as $associationName) {
                if ($this->exportQueryProvider->isAssociationExportable($metadata, $associationName)) {
                    $targetClass = $metadata->getAssociationTargetClass($associationName);
                    $targetMetadata = $entityManager->getClassMetadata($targetClass);

                    // +1 for relation id column.
                    $tupleLength = 1;
                    // For target entity fields.
                    $tupleLength += count($targetMetadata->getFieldNames());
                    // For target entity relation id columns.
                    $tupleLength += count(
                        array_filter(
                            $targetMetadata->getAssociationNames(),
                            function (string $fieldName) use ($targetMetadata) {
                                return $this->exportQueryProvider->isAssociationExportable(
                                    $targetMetadata,
                                    $fieldName
                                );
                            }
                        )
                    );

                    $this->tupleLengthByClassName[$className] += $tupleLength;
                }
            }
        }

        return $this->tupleLengthByClassName[$className];
    }
}
