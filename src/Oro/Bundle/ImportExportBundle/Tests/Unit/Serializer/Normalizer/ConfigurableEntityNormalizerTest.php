<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\ImportExport\Serializer\Normalizer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\ConfigurableEntityNormalizer;

class ConfigurableEntityNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldHelper;

    /**
     * @var ConfigurableEntityNormalizer
     */
    protected $normalizer;

    protected function setUp()
    {
        $this->fieldHelper = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Field\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new ConfigurableEntityNormalizer($this->fieldHelper);
    }

    /**
     * @dataProvider supportDenormalizationDataProvider
     * @param mixed $data
     * @param string $type
     * @param bool $hasConfig
     * @param bool $isSupported
     */
    public function testSupportsDenormalization($data, $type, $hasConfig, $isSupported)
    {
        if (is_array($data) && class_exists($type)) {
            $this->fieldHelper->expects($this->once())
                ->method('hasConfig')
                ->will($this->returnValue($hasConfig));
        } else {
            $this->fieldHelper->expects($this->never())
                ->method('hasConfig');
        }
        $this->assertEquals($isSupported, $this->normalizer->supportsDenormalization($data, $type));
    }

    /**
     * @return array
     */
    public function supportDenormalizationDataProvider()
    {
        return array(
            array(null, null, false, false),
            array('test', null, false, false),
            array('test', 'stdClass', false, false),
            array(array(), null, false, false),
            array(array(), 'stdClass', false, false),
            array(array(), 'stdClass', true, true)
        );
    }

    /**
     * @dataProvider supportsNormalizationDataProvider
     * @param mixed $data
     * @param bool $hasConfig
     * @param bool $isSupported
     */
    public function testSupportsNormalization($data, $hasConfig, $isSupported)
    {
        if (is_object($data)) {
            $this->fieldHelper->expects($this->once())
                ->method('hasConfig')
                ->will($this->returnValue($hasConfig));
        } else {
            $this->fieldHelper->expects($this->never())
                ->method('hasConfig');
        }

        $this->assertEquals($isSupported, $this->normalizer->supportsNormalization($data));
    }

    /**
     * @return array
     */
    public function supportsNormalizationDataProvider()
    {
        return array(
            array(null, false, false),
            array(null, true, false),
            array('test', false, false),
            array('test', true, false),
            array(new \stdClass(), false, false),
            array(new \stdClass(), true, true),
        );
    }

    /**
     * @codingStandardsIgnoreStart
     * @expectedException \Symfony\Component\Serializer\Exception\InvalidArgumentException
     * @expectedExceptionMessage Serializer must implement "Symfony\Component\Serializer\Normalizer\NormalizerInterface" and "Symfony\Component\Serializer\Normalizer\DenormalizerInterface"
     * @codingStandardsIgnoreEnd
     */
    public function testSetSerializerException()
    {
        $serializer = $this->getMock('Symfony\Component\Serializer\SerializerInterface');
        $this->normalizer->setSerializer($serializer);
    }

    public function testSetSerializer()
    {
        $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->normalizer->setSerializer($serializer);
        $this->assertAttributeSame($serializer, 'serializer', $this->normalizer);
    }

    /**
     * @dataProvider normalizeDataProvider
     * @param object $object
     * @param array $context
     * @param array $fields
     * @param array $fieldsImportConfig
     * @param array $result
     */
    public function testNormalize($object, $context, $fields, $fieldsImportConfig, $result)
    {
        $format = null;
        $entityName = get_class($object);
        $this->fieldHelper->expects($this->atLeastOnce())
            ->method('getFields')
            ->with($entityName, true)
            ->will($this->returnValue($fields));

        $configValueMap = array();
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            foreach ($fieldsImportConfig[$fieldName] as $configKey => $configValue) {
                $configValueMap[] = array($entityName, $fieldName, $configKey, null, $configValue);
            }
        }
        $this->fieldHelper->expects($this->any())
            ->method('getConfigValue')
            ->will($this->returnValueMap($configValueMap));

        $serializer = $this->getMockBuilder('Symfony\Component\Serializer\Serializer')
            ->disableOriginalConstructor()
            ->getMock();

        if (isset($field['normalizedValue'])) {
            $serializer->expects($this->once())
                ->method('normalize')
                ->with($field['value'], $format, $field['fieldContext'])
                ->will($this->returnValue($field['normalizedValue']));
        }

        $this->assertEquals($result, $this->normalizer->normalize($object, $format, $context));
    }

    public function normalizeDataProvider()
    {
        $object = (object) array(
            'fieldString' => 'string',
            'fieldObject' => new \stdClass(),
            'excluded' => 'excluded',
            'id' => 'id',
            'nonId' => 'nonId',
            'objectNoIds' => new \stdClass()
        );

        return array(
            'simple' => array(
                $object,
                array(),
                array(
                    array(
                        'name' => 'fieldString'
                    )
                ),
                array(
                    'fieldString' => array(
                        'excluded' => false
                    )
                ),
                array(
                    'fieldString' => 'string'
                )
            ),
            'simple_with_excluded' => array(
                $object,
                array(),
                array(
                    array(
                        'name' => 'fieldString'
                    ),
                    array(
                        'name' => 'id'
                    )
                ),
                array(
                    'fieldString' => array(
                        'excluded' => true
                    ),
                    'id' => array(
                        'excluded' => false
                    )
                ),
                array(
                    'id' => 'id'
                )
            )
        );
    }
}
