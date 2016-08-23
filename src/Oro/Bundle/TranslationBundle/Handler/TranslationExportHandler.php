<?php

namespace Oro\Bundle\TranslationBundle\Handler;

use Oro\Bundle\ImportExportBundle\Handler\ExportHandler;

use Oro\Bundle\TranslationBundle\Entity\Language;

class TranslationExportHandler extends ExportHandler
{
    /**
     * @var Language
     */
    protected $language;

    /**
     * {@inheritdoc}
     */
    protected function generateExportFileName($prefix, $extension)
    {
        if (($this->language instanceof Language) && ($this->language->getCode())) {
            return parent::generateExportFileName('translations--' . $this->language->getCode() . '--', $extension);
        }

        return parent::generateExportFileName($prefix, $extension);
    }

    /**
     * @param Language $language
     *
     * @return $this
     */
    public function setLanguage(Language $language)
    {
        $this->language = $language;

        return $this;
    }
}
 