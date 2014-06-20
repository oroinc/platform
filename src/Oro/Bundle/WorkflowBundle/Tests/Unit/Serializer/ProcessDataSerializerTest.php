<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Serializer;

use Oro\Bundle\WorkflowBundle\Serializer\Normalizer\ProcessObjectNormalizer;
use Oro\Bundle\WorkflowBundle\Serializer\ProcessDataSerializer;

class ProcessDataSerializerTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalizeAndDenormalize()
    {
        $normalizer = new ProcessObjectNormalizer();
        $serializer = new ProcessDataSerializer(array($normalizer));

        $originalObject = new \DateTime();

        $normalizedObject = $serializer->normalize($originalObject);
        $this->assertAttributeEmpty('normalizerCache', $serializer);

        $denormalizedObject = $serializer->denormalize($normalizedObject, null);
        $this->assertAttributeEmpty('denormalizerCache', $serializer);

        $this->assertEquals($originalObject, $denormalizedObject);
    }
}
