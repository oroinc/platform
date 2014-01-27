<?php

namespace Oro\Bundle\EmbeddedFormBundle\Grid\Formatter;


use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\AbstractProperty;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Symfony\Component\Translation\TranslatorInterface;

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
        return $this->translator->trans($this->manager->getLabelByType($record->getValue('formType')));
    }
}
