<?php

namespace Oro\Bundle\LDAPBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;
use Oro\Bundle\ImportExportBundle\Converter\DataConverterInterface;

class LdapUserDataConverter extends AbstractTableDataConverter implements ContextAwareInterface
{

    /** @var ContextInterface */
    protected $context;

    /**
     * Get list of rules that should be user to convert,
     *
     * Example: array(
     *     'User Name' => 'userName', // key is frontend hint, value is backend hint
     *     'User Group' => array(     // convert data using regular expression
     *         self::FRONTEND_TO_BACKEND => array('User Group (\d+)', 'userGroup:$1'),
     *         self::BACKEND_TO_FRONTEND => array('userGroup:(\d+)', 'User Group $1'),
     *     )
     * )
     *
     * @return array
     */
    protected function getHeaderConversionRules()
    {
        return [
            'username' => 'dn',
        ];
    }

    /**
     * Get maximum backend header for current entity
     *
     * @return array
     */
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }

    /**
     * @param ContextInterface $context
     */
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }
}