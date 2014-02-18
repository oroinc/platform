<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\MergeRenderListener;

use Oro\Bundle\EntityMergeBundle\Event\FieldValueRenderEvent;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\LocaleBundle\Model\NamePrefixInterface;
use Oro\Bundle\LocaleBundle\Model\NameSuffixInterface;

class ConvertDefaultValueTypesListener
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
     * @param object $entity
     * @return string
     */
    protected function getObjectsStringRepresentative($entity)
    {
        if ($entity instanceof AddressInterface) {
            return $this->addressFormatter->format($entity);
        }
        if ($entity instanceof NamePrefixInterface ||
            $entity instanceof FirstNameInterface ||
            $entity instanceof LastNameInterface ||
            $entity instanceof NameSuffixInterface
        ) {

            return $this->nameFormatter->format($entity);
        }
        if ($entity instanceof \DateTime) {
            return $this->dateTimeFormatter->format($entity);
        }

        return false;
    }

    /**
     * @param FieldValueRenderEvent $fieldValueEvent
     */
    public function afterCalculate(FieldValueRenderEvent $fieldValueEvent)
    {
        $entity = $fieldValueEvent->getEntity();

        if (is_object($entity)) {
            $entityStringRepresentative = $this->getObjectsStringRepresentative($entity);
            if ($entityStringRepresentative !== false) {
                $fieldValueEvent->setFieldValue($entityStringRepresentative);
            }
        } else {
            if (is_numeric($entity)) {
                $metadata = $fieldValueEvent->getMetadata();
                $formatter_type = $metadata->get('number_formatter_type');
                if (empty($formatter_type)) {
                    $formatter_type = 'default_style';
                }
                $entityStringRepresentative = $this->numberFormatter->format($entity, $formatter_type);
                if ($entityStringRepresentative !== false) {
                    $fieldValueEvent->setFieldValue($entityStringRepresentative);
                }
            }
        }

    }
}
