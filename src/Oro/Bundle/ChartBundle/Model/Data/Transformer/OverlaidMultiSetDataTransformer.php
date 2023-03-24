<?php

namespace Oro\Bundle\ChartBundle\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;

/**
 * Transforms data to chart specific format.
 * Uses first line labels for every data lines in order to overlay all data sets on the first data set.
 * Adds an original label as a value for `originalLabel` key.
 */
class OverlaidMultiSetDataTransformer implements TransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform(DataInterface $data, array $chartOptions)
    {
        $data = $data->toArray();
        if (!$data) {
            return new ArrayData([]);
        }

        $result = [];

        $baseSetIndex = -1;
        $firstLineName = null;
        foreach ($data as $lineName => $lineData) {
            $baseSetIndex++;
            $seriesData = [];

            foreach ($lineData as $lineIndex => $lineItem) {
                $label = $lineItem['label'];
                $value = $lineItem['value'];

                /*
                 * label - First line labels are used for every data lines in order to overlay all ranges
                 * on the first period;
                 * originalLabel - this value will be used in the point tooltip, e.g. "Mar 6, 2023";
                 * startLabel and endLabel - this values can be used in tooltip
                 * instead of originalLabel in case of range, e.g. "Mar 6, 2023 - Mar 12, 2023".
                 */
                $commonData = [
                    'value' => $value,
                    'label' => $firstLineName ? $result[$firstLineName][$lineIndex]['label'] : $label,
                    'originalLabel' => $label
                ];
                $tooltipData = [];
                if (isset($lineItem['startLabel'], $lineItem['endLabel'])) {
                    $tooltipData = [
                        'startLabel' => $lineItem['startLabel'],
                        'endLabel' => $lineItem['endLabel'],
                    ];
                }

                $seriesData[] = array_merge($commonData, $tooltipData);
            }

            if ($baseSetIndex === 0) {
                $firstLineName = $lineName;
            }

            $result[$lineName] = $seriesData;
        }

        return new ArrayData($result);
    }
}
