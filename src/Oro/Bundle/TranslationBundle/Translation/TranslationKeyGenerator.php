<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\Rules\English\InflectorFactory;

class TranslationKeyGenerator
{
    private Inflector $inflector;

    public function __construct()
    {
        $this->inflector = (new InflectorFactory())->build();
    }

    /**
     * @param TranslationKeySourceInterface $source
     * @return string
     */
    public function generate(TranslationKeySourceInterface $source)
    {
        $translationKey = (string)$source->getTemplate();
        $data = $source->getData();

        foreach ($data as $key => $value) {
            $translationKey = $this->replaceData($translationKey, $key, $value);
        }

        return $translationKey;
    }

    /**
     * @param string $data
     * @param string $key
     * @param string $value
     * @return string
     */
    protected function replaceData($data, $key, $value)
    {
        $value = $this->inflector->tableize($value);
        $value = preg_replace('/\s+/', '_', trim($value));

        return str_replace('{{ ' . $key . ' }}', $value, $data);
    }
}
