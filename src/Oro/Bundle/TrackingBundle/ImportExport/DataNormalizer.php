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
        $denormalized['data']  = json_encode($data);
        $denormalized['event'] = $data;
        $denormalized['event']['website'] = ['identifier' => $denormalized['event']['website']];

        return parent::denormalize($denormalized, $class, $format, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = array())
    {
        return is_array($data) && $type == $this->entityName;
    }
}
