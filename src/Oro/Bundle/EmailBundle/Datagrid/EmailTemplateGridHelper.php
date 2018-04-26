<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\Grid\GridHelper as BaseGridHelper;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Symfony\Component\Translation\TranslatorInterface;

class EmailTemplateGridHelper extends BaseGridHelper
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * Constructor
     *
     * @param EntityProvider      $entityProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(EntityProvider $entityProvider, TranslatorInterface $translator)
    {
        parent::__construct($entityProvider);
        $this->translator = $translator;
    }

    /**
     * Returns callback for configuration of grid/actions visibility per row
     *
     * @return callable
     */
    public function getActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            if ($record->getValue('isSystem')) {
                return ['delete' => false];
            }
        };
    }

    /**
     * Returns email template type choice list
     *
     * @return array
     */
    public function getTypeChoices()
    {
        return [
            'oro.email.datagrid.emailtemplate.filter.type.html' => 'html',
            'oro.email.datagrid.emailtemplate.filter.type.txt' => 'txt',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityNames()
    {
        $result = [];
        $result[$this->translator->trans('oro.email.datagrid.emailtemplate.filter.entityName.empty')] = '_empty_';

        $result = array_merge($result, parent::getEntityNames());

        return $result;
    }
}
