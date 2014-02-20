<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\Render;

use Oro\Bundle\EntityMergeBundle\Event\ValueRenderEvent;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\LocaleBundle\Model\MiddleNameInterface;
use Oro\Bundle\LocaleBundle\Model\NamePrefixInterface;
use Oro\Bundle\LocaleBundle\Model\NameSuffixInterface;

class LocalizedValueRenderListener
{
    /**
     * @var AddressFormatter
     */
    protected $addressFormatter;

    /**
     * @var DateTimeFormatter
     */
    protected $dateTimeFormatter;

    /**
     * @var NameFormatter
     */
    protected $nameFormatter;

    /**
     * @var NumberFormatter
     */
    protected $numberFormatter;

    /**
     * @param AddressFormatter $addressFormatter
     * @param DateTimeFormatter $dateTimeFormatter
     * @param NameFormatter $nameFormatter
     * @param NumberFormatter $numberFormatter
     */
    public function __construct(
        AddressFormatter $addressFormatter,
        DateTimeFormatter $dateTimeFormatter,
        NameFormatter $nameFormatter,
        NumberFormatter $numberFormatter
    ) {
        $this->addressFormatter = $addressFormatter;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->nameFormatter = $nameFormatter;
        $this->numberFormatter = $numberFormatter;
    }

    /**
     * @param ValueRenderEvent $fieldValueEvent
     */
    public function beforeValueRender(ValueRenderEvent $fieldValueEvent)
    {
        $originalValue = $fieldValueEvent->getOriginalValue();
        $metadata = $fieldValueEvent->getMetadata();

        if ($originalValue instanceof AddressInterface) {
            $fieldValueEvent->setConvertedValue($this->addressFormatter->format($originalValue));
        } elseif ($originalValue instanceof NamePrefixInterface ||
            $originalValue instanceof FirstNameInterface ||
            $originalValue instanceof MiddleNameInterface ||
            $originalValue instanceof LastNameInterface ||
            $originalValue instanceof NameSuffixInterface
        ) {
            $fieldValueEvent->setConvertedValue($this->nameFormatter->format($originalValue));
        } elseif ($originalValue instanceof \DateTime) {
            $dateType = $metadata->get('render_date_type');
            $timeType = $metadata->get('render_time_type');

            $dateTimePattern = $metadata->get('render_datetime_pattern');

            $fieldValueEvent->setConvertedValue(
                $this->dateTimeFormatter->format($originalValue, $dateType, $timeType, null, null, $dateTimePattern)
            );
        } elseif (is_numeric($originalValue)) {
            $numberStyle = $metadata->get('render_number_style');
            if (!$numberStyle) {
                $numberStyle = 'default_style';
            }
            $fieldValueEvent->setConvertedValue($this->numberFormatter->format($originalValue, $numberStyle));
        }
    }
}
