<?php

namespace Oro\Bundle\BatchBundle\ORM\Query;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

interface BufferedQueryResultIteratorInterface extends \Iterator, \Countable
{
    /**
     * Gets the source query.
     *
     * @return Query|QueryBuilder|object
     */
    public function getSource();

    /**
     * Sets the maximum number of records that can be loaded from the database.
     *
     * @param int $bufferSize
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If the given buffer size is not valid
     */
    public function setBufferSize($bufferSize);

    /**
     * Sets callback to be called after a page iteration was finished.
     *
     * @param callable|null $callback
     *
     * @return $this
     */
    public function setPageCallback(callable $callback = null);

    /**
     * Sets callback to be called after a page is loaded.
     *
     * @param callable|null $callback (array $rows): array $rows
     *
     * @return $this
     */
    public function setPageLoadedCallback(callable $callback = null);

    /**
     * Sets the hydration mode to be used to get results.
     * If the hydration mode is not set, it will be computed automatically.
     *
     * @param int|string $hydrationMode
     *
     * @return $this
     */
    public function setHydrationMode($hydrationMode);
}
