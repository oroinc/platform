<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Validator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ImportExportBundle\Validator\IdentityValidationLoader;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class IdentityValidationLoaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var IdentityValidationLoader */
    private $loader;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldConfigProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->fieldConfigProvider = $this->createMock(ConfigProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->loader = new IdentityValidationLoader(
            $this->extendConfigProvider,
            $this->fieldConfigProvider,
            $this->doctrineHelper
        );
    }

    public function testLoadMetadataAndEntityHasNoConfig()
    {
        $this->loader->addConstraints(
            'integer',
            [
                [
                    'Regex' => [
                        'pattern' => '/^\-{0,1}[\d+]*$/',
                        'message' => 'This value should contain only numbers.',
                        'groups' => ['import_identity']
                    ]
                ]
            ]
        );

        $className = '\StdClass';
        $classMetaData = new ClassMetadata($className);
        $this->fieldConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(false);

        $this->fieldConfigProvider
            ->expects($this->never())
            ->method('getConfigs')
            ->with($className);

        $this->loader->loadClassMetadata($classMetaData);
    }

    public function testLoadMetadata()
    {
        $this->loader->addConstraints(
            'integer',
            [
                [
                    'Regex' => [
                        'pattern' => '/^\-{0,1}[\d+]*$/',
                        'message' => 'This value should contain only numbers.',
                        'groups' => ['import_identity']
                    ]
                ]
            ]
        );

        $className = User::class;
        $classMetaData = new ClassMetadata($className);
        $this->fieldConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with($className)
            ->willReturn(true);

        $this->fieldConfigProvider
            ->expects($this->once())
            ->method('getConfigs')
            ->with($className)
            ->willReturn(
                $this->getFieldConfigs(
                    $className,
                    ['id' => 'integer']
                )
            );

        $extendConfigFieldMock = $this->createMock(ConfigInterface::class);
        $extendConfigFieldMock
            ->expects($this->exactly(2))
            ->method('is')
            ->withConsecutive(
                ['is_deleted'],
                ['state', 'Active']
            )
            ->willReturnOnConsecutiveCalls(false, true);

        $this->extendConfigProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with($className, 'id')
            ->willReturn($extendConfigFieldMock);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with($className, false)
            ->willReturn('id');

        $this->loader->loadClassMetadata($classMetaData);

        $this->assertCount(1, $classMetaData->getConstrainedProperties());
    }

    /**
     * @param string $className
     * @param array $fieldConfigArray
     * @return array
     */
    protected function getFieldConfigs($className, $fieldConfigArray): array
    {
        $fieldConfigs = [];
        foreach ($fieldConfigArray as $fieldName => $fieldType) {
            $fieldConfigs[] = new Config(
                new FieldConfigId(
                    'app',
                    $className,
                    $fieldName,
                    $fieldType
                )
            );
        }

        return $fieldConfigs;
    }
}
