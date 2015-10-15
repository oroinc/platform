<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Formatter\FormatterProvider;

class DateTimeFormatter implements NormalizerInterface
{
    /**
     * @var FormatterProvider
     */
    protected $formatterProvider;

    /**
     * @param FormatterProvider $formatterProvider
     */
    public function __construct(FormatterProvider $formatterProvider)
    {
        $this->formatterProvider = $formatterProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof \DateTime &&
        isset($context[FormatterProvider::FORMAT_TYPE]) &&
        in_array($context['type'], ['datetime', 'date', 'time']);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $type       = $context['type'];
        $formatType = $context[FormatterProvider::FORMAT_TYPE];
        $formatter  = $this->formatterProvider->getFormatterFor($formatType, $type);

        return $formatter->formatType($object, $type);
    }
}
