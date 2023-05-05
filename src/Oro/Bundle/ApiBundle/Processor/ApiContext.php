<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Component\ChainProcessor\Context as BaseContext;

/**
 * The base execution context for API processors.
 */
abstract class ApiContext extends BaseContext
{
    /** the request type */
    public const REQUEST_TYPE = 'requestType';

    /** API version */
    public const VERSION = 'version';

    private ?array $processed = null;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Sets default values into the context.
     */
    protected function initialize(): void
    {
        $this->set(self::REQUEST_TYPE, new RequestType([]));
    }

    /**
     * Gets the current request type.
     * A request can belong to several types, e.g. "rest" and "json_api".
     */
    public function getRequestType(): RequestType
    {
        return $this->get(self::REQUEST_TYPE);
    }

    /**
     * Gets API version.
     */
    public function getVersion(): string
    {
        return $this->get(self::VERSION) ?? '';
    }

    /**
     * Sets API version.
     */
    public function setVersion(string $version): void
    {
        $this->set(self::VERSION, $version);
    }

    /**
     * Marks a work as already done.
     * In the most cases this method is useless because it is easy to determine
     * when a work is already done by checking a state of a context.
     * However, a processor performs a complex work, it might be required
     * to mark a work as already done directly.
     */
    public function setProcessed(string $operationName): void
    {
        $this->processed[$operationName] = true;
    }

    /**
     * Marks a work as not yet done.
     */
    public function clearProcessed(string $operationName): void
    {
        if ($this->isProcessed($operationName)) {
            unset($this->processed[$operationName]);
        }
    }

    /**
     * Checks whether a work is already done.
     */
    public function isProcessed(string $operationName): bool
    {
        return
            null !== $this->processed
            && \array_key_exists($operationName, $this->processed)
            && $this->processed[$operationName];
    }
}
