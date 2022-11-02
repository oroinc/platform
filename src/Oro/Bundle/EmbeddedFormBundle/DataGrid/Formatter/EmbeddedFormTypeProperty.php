<?php

namespace Oro\Bundle\EmbeddedFormBundle\DataGrid\Formatter;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\AbstractProperty;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Translates embedded form names
 */
class EmbeddedFormTypeProperty extends AbstractProperty
{
    /**
     * @var EmbeddedFormManager
     */
    protected $manager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(EmbeddedFormManager $manager, TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->translator = $translator;
    }

    /**
     * @param ResultRecordInterface $record
     *
     * @return mixed
     */
    protected function getRawValue(ResultRecordInterface $record)
    {
        $label = (string) $this->manager->getLabelByType($record->getValue('formType'));

        return $this->translator->trans($label);
    }
}
