<?php

namespace Oro\Bundle\TranslationBundle\Handler;

use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;

class TranslationExportHandler extends ExportHandler
{
    /**
     * @var string
     */
    protected $languageCode = 'en';

    /**
     * {@inheritdoc}
     */
    protected function generateExportFileName($prefix, $extension)
    {
        return parent::generateExportFileName('translations--' . $this->languageCode . '--', $extension);
    }

    /**
     * @param string $languageCode
     *
     * @return $this
     */
    public function setLanguageCode($languageCode)
    {
        $this->languageCode = $languageCode;

        return $this;
    }
}
