<?php

namespace Oro\Bundle\EmailBundle\Datagrid;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityBundle\Grid\GridHelper as BaseGridHelper;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;

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
                return array('delete' => false);
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
            'html' => 'oro.email.datagrid.emailtemplate.filter.type.html',
            'txt'  => 'oro.email.datagrid.emailtemplate.filter.type.txt'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityNames()
    {
        $result            = [];
        $result['_empty_'] = $this->translator->trans('oro.email.datagrid.emailtemplate.filter.entityName.empty');

        $result = array_merge($result, parent::getEntityNames());

        return $result;
    }
}
