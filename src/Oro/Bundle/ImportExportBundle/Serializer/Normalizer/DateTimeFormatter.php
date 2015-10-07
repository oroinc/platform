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
        return $data instanceof \DateTime && isset($context[FormatterProvider::FORMATTER_PROVIDER][$context['type']]);
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $type                = $context['type'];
        $formatterTypePrefix = FormatterProvider::FORMAT_TYPE_PREFIX;
        $alias               = $context[FormatterProvider::FORMATTER_PROVIDER][$type];
        $formatter           = $this->formatterProvider->getFormatter($alias);

        return $formatter->formatType($object, $formatterTypePrefix . $type);
    }
}
