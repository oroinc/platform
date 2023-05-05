<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Datagrid Field Property formatter
 */
class FieldProperty extends AbstractProperty
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $type = $this->getOr(self::FRONTEND_TYPE_KEY);
        if ($type === self::TYPE_SELECT || $type === self::TYPE_MULTI_SELECT) {
            $choices = $this->getOr('choices', []);
            if ($this->getOr('translatable_options', true)) {
                $translated = [];
                foreach ($choices as $key => $val) {
                    $translated[$key ? $this->translator->trans((string)$key) : ''] = $val;
                }
                $choices = $translated;
            }
            $this->params['choices'] = $choices;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getRawValue(ResultRecordInterface $record)
    {
        try {
            $value = $record->getValue($this->getOr(self::DATA_NAME_KEY) ?: $this->get(self::NAME_KEY));
            if ($this->getOr(self::FRONTEND_TYPE_KEY) === self::TYPE_MULTI_SELECT) {
                $value = explode(',', $value);
            }
            $value = $this->applyDivisor($value);
        } catch (\LogicException $e) {
            // default value
            $value = null;
        }

        return $value;
    }

    /**
     * Apply configured divisor to a numeric raw value
     *
     * @param mixed $value
     *
     * @return float
     */
    protected function applyDivisor($value)
    {
        if (!is_numeric($value)) {
            return $value;
        }

        if ($divisor = $this->getOr(self::DIVISOR_KEY)) {
            $value = $value / $divisor;
        }

        return $value;
    }
}
