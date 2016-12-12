<?php

namespace Oro\Bundle\TranslationBundle\Translation;

use Doctrine\Common\Inflector\Inflector;

class TranslationKeyGenerator
{
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
        $value = Inflector::tableize($value);
        $value = preg_replace('/\s+/', '_', trim($value));

        return str_replace('{{ ' . $key . ' }}', $value, $data);
    }
}
