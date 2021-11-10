<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Provider;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SoapBundle\Controller\Api\EntityManagerAwareInterface;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Oro\Bundle\SoapBundle\Provider\EntityMetadataProvider;
use Oro\Bundle\SoapBundle\Tests\Unit\Entity\Manager\Stub\Entity;
use Symfony\Contracts\Translation\TranslatorInterface;

class EntityMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $cm;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var EntityMetadataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->cm = $this->createMock(ConfigManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new EntityMetadataProvider($this->cm, $this->translator);
    }

    public function testGetMetadataFromUnknownObject(): void
    {
        $result = $this->provider->getMetadataFor(new \stdClass());

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @dataProvider configValuesProvider
     */
    public function testGetMetadata(
        string $entityName,
        bool $hasConfig,
        array $configValuesMap,
        array $expectedResult
    ): void {
        $classMetadata = new ClassMetadataInfo($entityName);
        $object = $this->createMock(EntityManagerAwareInterface::class);

        $apiManager = $this->createMock(ApiEntityManager::class);
        $apiManager->expects(self::once())
            ->method('getMetadata')
            ->willReturn($classMetadata);
        $object->expects(self::once())
            ->method('getManager')
            ->willReturn($apiManager);
        $this->cm->expects(self::once())
            ->method('hasConfig')
            ->willReturn($hasConfig);

        if ($hasConfig) {
            $config = $this->createMock(ConfigInterface::class);

            $this->cm->expects(self::once())
                ->method('getConfig')
                ->with(new EntityConfigId('entity', $entityName))
                ->willReturn($config);

            $config->expects(self::any())
                ->method('get')
                ->willReturnMap($configValuesMap);
            $this->translator->expects(self::any())
                ->method('trans')
                ->willReturnArgument(0);
        }

        $result = $this->provider->getMetadataFor($object);
        self::assertIsArray($result);
        self::assertEquals($expectedResult, $result);
    }

    public function configValuesProvider(): array
    {
        return [
            'Not configurable entity given' => [
                '$entityName' => Entity::class,
                '$hasConfig' => false,
                '$configValuesMap' => [],
                '$expectedResult' => [
                    'entity' => [
                        'phpType' => Entity::class,
                    ],
                ],
            ],
            'Configurable entity given, full set of metadata' => [
                '$entityName' => Entity::class,
                '$hasConfig' => true,
                '$configValuesMap' => [
                    ['label', false, null, 'StubEntity'],
                    ['plural_label', false, null, 'StubEntities'],
                    ['description', false, null, 'Used for unit tests in OroSoapBundle'],
                ],
                '$expectedResult' => [
                    'entity' => [
                        'phpType' => Entity::class,
                        'label' => 'StubEntity',
                        'pluralLabel' => 'StubEntities',
                        'description' => 'Used for unit tests in OroSoapBundle',
                    ],
                ],
            ],
        ];
    }
}
