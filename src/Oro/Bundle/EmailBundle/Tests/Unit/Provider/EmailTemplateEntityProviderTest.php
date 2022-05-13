<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateEntityProvider;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Provider\ExclusionProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailTemplateEntityProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $extendConfigProvider;

    /** @var EntityClassResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityClassResolver;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /** @var ExclusionProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $exclusionProvider;

    /** @var EmailTemplateEntityProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->exclusionProvider = $this->createMock(ExclusionProviderInterface::class);

        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($entityName) {
                return str_replace(':', '\\Entity\\', $entityName);
            });

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->provider = new EmailTemplateEntityProvider(
            $this->entityConfigProvider,
            $this->extendConfigProvider,
            $this->entityClassResolver,
            $translator,
            $this->featureChecker
        );
        $this->provider->setExclusionProvider($this->exclusionProvider);
    }

    private function getEntityConfig(string $entityClassName, array $values, string $scope = 'entity'): Config
    {
        $entityConfigId = new EntityConfigId($scope, $entityClassName);
        $entityConfig = new Config($entityConfigId);
        $entityConfig->setValues($values);

        return $entityConfig;
    }

    public function testGetEntities(): void
    {
        $entityClassName1 = 'Acme\Entity\Test1';
        $entityClassName2 = 'Acme\Entity\Test2';

        $entityConfig1 = $this->getEntityConfig(
            $entityClassName1,
            [
                'label'        => 'C',
                'plural_label' => 'B',
                'icon'         => 'fa-test1',
            ]
        );
        $entityConfig2 = $this->getEntityConfig(
            $entityClassName2,
            [
                'label'        => 'B',
                'plural_label' => 'A',
                'icon'         => 'fa-test2',
            ]
        );
        $emailConfig = $this->getEntityConfig(
            Email::class,
            [
                'label'        => 'Email',
                'plural_label' => 'Emails',
                'icon'         => 'fa-test3',
            ]
        );

        $map = [
            $entityClassName1 => $entityConfig1,
            $entityClassName2 => $entityConfig2
        ];

        $this->extendConfigProvider->expects($this->any())
            ->method('getConfigById')
            ->willReturnCallback(function (EntityConfigId $configId) use ($map) {
                $className = $configId->getClassName();

                /** @var ConfigInterface $config */
                $config = $map[$className];
                $config->set('state', ExtendScope::STATE_ACTIVE);

                return $config;
            });

        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(Email::class)
            ->willReturn($emailConfig);

        $this->entityConfigProvider->expects($this->any())
            ->method('getConfigs')
            ->willReturn([$entityConfig1, $entityConfig2]);

        $this->featureChecker->expects($this->any())
            ->method('isResourceEnabled')
            ->willReturn(true);

        $result   = $this->provider->getEntities();
        $expected = [
            [
                'name'         => $entityClassName2,
                'label'        => 'B',
                'plural_label' => 'A',
                'icon'         => 'fa-test2',
            ],
            [
                'name'         => $entityClassName1,
                'label'        => 'C',
                'plural_label' => 'B',
                'icon'         => 'fa-test1',
            ],
            [
                'name'         => Email::class,
                'label'        => 'Email',
                'plural_label' => 'Emails',
                'icon'         => 'fa-test3',
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}
