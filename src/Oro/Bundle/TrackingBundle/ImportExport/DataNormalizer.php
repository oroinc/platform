<?php

namespace Oro\Bundle\TrackingBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;

class DataNormalizer extends ConfigurableEntityNormalizer implements EntityNameAwareInterface
{
    const DEFAULT_NAME = 'visit';

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @param string $entityName
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = array())
    {
        return parent::denormalize(
            $this->updateData($data),
            $class,
            $format,
            $context
        );
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        throw new \Exception('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return is_array($data) && $type == $this->entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = array())
    {
        return false;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function updateData(array $data)
    {
        $result          = [];
        $result['data']  = json_encode($data);

        if (empty($data['name'])) {
            $data['name'] = self::DEFAULT_NAME;
        }
        if (!isset($data['value'])) {
            $data['value'] = 1;
        }

        $result['event'] = $data;

        if (!empty($result['event']['website'])) {
            $result['event']['website'] = [
                'identifier' => $result['event']['website']
            ];
        }

        return $result;
    }
}
