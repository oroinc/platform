<?php

namespace Oro\Bundle\DataGridBundle\Extension\Formatter\Property;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FieldProperty extends AbstractProperty
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
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
            $translator = $this->translator;

            $choices = $this->getOr('choices', []);
            $translated = [];
            array_walk(
                $choices,
                function ($item, $key) use ($translator, &$translated) {
                    $translated[$translator->trans($key)] = $item;
                }
            );

            $this->params['choices'] = $translated;
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
