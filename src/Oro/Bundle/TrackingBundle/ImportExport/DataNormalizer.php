<?php

namespace Oro\Bundle\TrackingBundle\ImportExport;

use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;

class DataNormalizer extends ConfigurableEntityNormalizer implements EntityNameAwareInterface
{
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
        $denormalized['data']  = json_encode($data);
        $denormalized['event'] = $data;

        if (!empty($denormalized['event']['website'])) {
            $denormalized['event']['website'] = [
                'identifier' => $denormalized['event']['website']
            ];
        }

        return $denormalized;
    }
}
