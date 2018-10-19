<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer;

use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessObjectNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessTraversableNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\ProcessDataSerializer;

class ProcessDataSerializerTest extends \PHPUnit\Framework\TestCase
{
    public function testNormalizeAndDenormalize()
    {
        $objectNormalizer = new ProcessObjectNormalizer();
        $traversableNormalizer = new ProcessTraversableNormalizer();
        $serializer = new ProcessDataSerializer(array($objectNormalizer, $traversableNormalizer));

        $originalData = array('old' => new \DateTime(), 'new' => new \DateTime());

        $normalizedData = $serializer->normalize($originalData);
        $denormalizedData = $serializer->denormalize($normalizedData, null);

        $this->assertEquals($originalData, $denormalizedData);
    }
}
