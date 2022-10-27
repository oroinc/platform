<?php
declare(strict_types=1);

namespace Oro\Component\DoctrineUtils\ORM;

use Doctrine\ORM\Internal\Hydration\AbstractHydrator;

/**
 * Hydrates result as an associative array where the fetched (single-column) result values are used as keys,
 * and boolean *true* is used as the value for all keys.
 *
 * Typical application - replace data shuffling with <code>\array_flip(\array_column())</code> on the results of
 * *ScalarHydrator* (or *ArrayHydrator* with single-column query).
 *
 * <code>\array_flip(\array_column($query->getArrayResult()))</code> and
 * <code>\array_flip(\array_column($query->getScalarResult()))</code> can be replaced with
 * <code>$query->getResult(ArrayKeyTrueHydrator::NAME)</code>, assuming that *ArrayKeyTrueHydrator::NAME*
 * is the hydration mode registered in the configuration, e.g. as follows:
 * <code>
 *      $query->getEntityManager()
 *          ->getConfiguration()
 *          ->addCustomHydrationMode(ArrayKeyTrueHydrator::NAME, ArrayKeyTrueHydrator::class);
 *
 *      $query->getResult(ArrayKeyTrueHydrator::NAME)
 * </code>
 */
class ArrayKeyTrueHydrator extends AbstractHydrator
{
    public const NAME = 'ArrayKeyTrueHydrator';

    protected function hydrateAllData(): array
    {
        $result = [];
        while ($data = $this->_stmt->fetch(\PDO::FETCH_COLUMN)) {
            $result[$data] = true;
        }

        return $result;
    }
}
