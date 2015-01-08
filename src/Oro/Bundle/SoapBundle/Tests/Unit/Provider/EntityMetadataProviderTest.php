<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SoapBundle\Provider\EntityMetadataProvider;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class EntityMetadataProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $cm;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var EntityMetadataProvider */
    protected $provider;

    protected function setUp()
    {
        $this->cm         = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()->getMock();
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->provider   = new EntityMetadataProvider($this->cm, $this->translator);
    }

    protected function tearDown()
    {
        unset($this->provider, $this->cm, $this->translator);
    }

    public function testGetMetadataFromUnknownObejct()
    {
        $result = $this->provider->getMetadataFor(new \stdClass());

        $this->assertInternalType('array', $result);
        $this->assertEmpty($result);
    }

    /**
     * @dataProvider configValuesProvider
     *
     * @param string $entityName
     * @param bool   $hasConfig
     * @param array  $configValuesMap
     * @param array  $expectedResult
     */
    public function testGetMetadata($entityName, $hasConfig, array $configValuesMap, array $expectedResult)
    {
        $classMetadata = new ClassMetadataInfo($entityName);
        $object        = $this->getMock('Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface');

        $apiManager = $this->getMockBuilder('Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager')
            ->disableOriginalConstructor()->getMock();
        $apiManager->expects($this->once())->method('getMetadata')->willReturn($classMetadata);
        $object->expects($this->once())->method('getManager')->willReturn($apiManager);
        $this->cm->expects($this->once())->method('hasConfig')->willReturn($hasConfig);

        if ($hasConfig) {
            $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');

            $this->cm->expects($this->once())->method('getConfig')->with(new EntityConfigId('entity', $entityName))
                ->willReturn($config);

            $config->expects($this->any())->method('get')->willReturnMap($configValuesMap);
            $this->translator->expects($this->any())->method('trans')->willReturnArgument(0);
        }

        $result = $this->provider->getMetadataFor($object);
        $this->assertInternalType('array', $result);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function configValuesProvider()
    {
        return [
            'Not configurable entity given'                   => [
                '$entityName'      => 'Oro\\Bundle\\SoapBundle\\Tests\\Unit\\Entity\\Manager\\Stub\\Entity',
                '$hasConfig'       => false,
                '$configValuesMap' => [],
                '$expectedResult'  => [
                    'entity' => [
                        'phpType' => 'Oro\\Bundle\\SoapBundle\\Tests\\Unit\\Entity\\Manager\\Stub\\Entity'
                    ]
                ],
            ],
            'Configurable entity given, full set of metadata' => [
                '$entityName'      => 'Oro\\Bundle\\SoapBundle\\Tests\\Unit\\Entity\\Manager\\Stub\\Entity',
                '$hasConfig'       => true,
                '$configValuesMap' => [
                    ['label', false, null, 'StubEntity'],
                    ['plural_label', false, null, 'StubEntities'],
                    ['description', false, null, 'Used for unit tests in OroSoapBundle'],
                ],
                '$expectedResult'  => [
                    'entity' => [
                        'phpType'     => 'Oro\\Bundle\\SoapBundle\\Tests\\Unit\\Entity\\Manager\\Stub\\Entity',
                        'label'       => 'StubEntity',
                        'pluralLabel' => 'StubEntities',
                        'description' => 'Used for unit tests in OroSoapBundle'
                    ]
                ]
            ]
        ];
    }
}
