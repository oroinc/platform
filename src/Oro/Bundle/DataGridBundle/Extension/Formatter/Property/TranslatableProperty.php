<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

/**
 * Property formatter for translation keys in the datagrids.
 */
class TranslatableProperty extends FieldProperty
{
    const TRANS_KEY = 'key';
    const PARAMS_KEY = 'params';
    const DIRECT_PARAMS_KEY = 'direct_params';
    const DOMAIN_KEY = 'domain';
    const LOCALE_KEY = 'locale';

    /** @var array */
    protected $excludeParams = [self::DOMAIN_KEY, self::LOCALE_KEY];

    /**
     * {@inheritdoc}
     */
    public function getRawValue(ResultRecordInterface $record)
    {
        $value = parent::getRawValue($record);
        if (!$value) {
            $value = (string) $this->getOr(self::TRANS_KEY, '');
        }

        return $this->translator->trans(
            $value,
            $this->getParameters($record),
            $this->getOr(self::DOMAIN_KEY),
            $this->getOr(self::LOCALE_KEY)
        );
    }

    protected function getParameters(ResultRecordInterface $record): array
    {
        $params = $this->getOr(self::DIRECT_PARAMS_KEY, []);
        foreach ($this->getOr(self::PARAMS_KEY, []) as $name => $valueKey) {
            if (is_numeric($name)) {
                $name = $valueKey;
            }

            $params[$name] = $record->getValue($valueKey);
        }

        return array_combine(
            array_map(
                static function ($key) {
                    return '%' . $key . '%';
                },
                array_keys($params)
            ),
            $params
        );
    }
}
