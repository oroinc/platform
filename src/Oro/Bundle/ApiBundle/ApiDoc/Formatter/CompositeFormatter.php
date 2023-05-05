<?php

namespace Oro\Bundle\ApiBundle\ApiDoc\Formatter;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Formatter\FormatterInterface;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;

/**
 * This formatter can be used to select appropriate formatter depending on a current view.
 */
class CompositeFormatter implements FormatterInterface
{
    private RestDocViewDetector $docViewDetector;
    /** @var FormatterInterface[] [view => formatter, ...] */
    private array $formatters = [];

    public function __construct(RestDocViewDetector $docViewDetector)
    {
        $this->docViewDetector = $docViewDetector;
    }

    public function addFormatter(string $view, FormatterInterface $formatter): void
    {
        $this->formatters[$view] = $formatter;
    }

    /**
     * {@inheritDoc}
     */
    public function format(array $collection)
    {
        return $this->getFormatter()->format($collection);
    }

    /**
     * {@inheritDoc}
     */
    public function formatOne(ApiDoc $annotation)
    {
        return $this->getFormatter()->formatOne($annotation);
    }

    private function getFormatter(): FormatterInterface
    {
        $view = $this->docViewDetector->getView();
        if (isset($this->formatters[$view])) {
            return $this->formatters[$view];
        }

        throw new \LogicException(sprintf('Cannot find formatter for "%s" API view.', $view));
    }
}
