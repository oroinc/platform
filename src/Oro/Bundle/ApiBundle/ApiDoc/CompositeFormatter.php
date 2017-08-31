<?php

namespace Oro\Bundle\ApiBundle\ApiDoc;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Formatter\FormatterInterface;

/**
 * This formatter can be used to select appropriate formatter depending on a current view.
 */
class CompositeFormatter implements FormatterInterface
{
    /** @var RestDocViewDetector */
    private $docViewDetector;

    /** @var FormatterInterface[] [view => formatter, ...] */
    private $formatters = [];

    /**
     * @param RestDocViewDetector $docViewDetector
     */
    public function __construct(RestDocViewDetector $docViewDetector)
    {
        $this->docViewDetector = $docViewDetector;
    }

    /**
     * @param string             $view
     * @param FormatterInterface $formatter
     */
    public function addFormatter($view, FormatterInterface $formatter)
    {
        $this->formatters[$view] = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $collection)
    {
        return $this->getFormatter()->format($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function formatOne(ApiDoc $annotation)
    {
        return $this->getFormatter()->formatOne($annotation);
    }

    /**
     * @return FormatterInterface
     */
    private function getFormatter()
    {
        $view = $this->docViewDetector->getView();
        if (isset($this->formatters[$view])) {
            return $this->formatters[$view];
        }

        return $this->formatters[''];
    }
}
