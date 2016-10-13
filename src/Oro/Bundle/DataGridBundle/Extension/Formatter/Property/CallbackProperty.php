<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class CallbackProperty extends AbstractProperty
{
    const CALLABLE_KEY = 'callable';
    const PARAMS_KEY = 'params';

    /** @var array */
    protected $excludeParams = [self::CALLABLE_KEY, self::PARAMS_KEY];

    /**
     * {@inheritdoc}
     */
    public function getRawValue(ResultRecordInterface $record)
    {
        return call_user_func_array($this->get(self::CALLABLE_KEY), $this->getParameters($record));
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    protected function getParameters(ResultRecordInterface $record)
    {
        $result = [];
        foreach ($this->getOr(self::PARAMS_KEY, []) as $name => $dataKey) {
            if (is_numeric($name)) {
                $name = $dataKey;
            }

            $result[$name] = $record->getValue($dataKey);
        }

        return $result ?: [$record];
    }
}
