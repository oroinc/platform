<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Provider;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityBundle\Configuration\EntityConfiguration;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;
use Oro\Bundle\EntityBundle\Model\EntityAlias;
use Oro\Bundle\EntityBundle\Provider\EntityAliasConfigBag;
use Oro\Bundle\EntityBundle\Provider\EntityAliasProvider;

class EntityAliasProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityAliasProvider */
    private $entityAliasProvider;

    protected function setUp(): void
    {
        $configProvider = $this->createMock(EntityConfigurationProvider::class);
        $configProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturnMap([
                [
                    EntityConfiguration::ENTITY_ALIASES,
                    [
                        'Test\EntityWithCustomAlias' => [
                            'alias'        => 'my_alias',
                            'plural_alias' => 'my_plural_alias'
                        ]
                    ]
                ],
                [
                    EntityConfiguration::ENTITY_ALIAS_EXCLUSIONS,
                    [
                        'Test\ExcludedEntity'
                    ]
                ]
            ]);

        $this->entityAliasProvider  = new EntityAliasProvider(
            new EntityAliasConfigBag($configProvider),
            (new InflectorFactory())->build()
        );
    }

    public function testGetClassNames()
    {
        $this->assertEntityAlias(
            ['Test\EntityWithCustomAlias'],
            $this->entityAliasProvider->getClassNames()
        );
    }

    /**
     * @dataProvider getEntityAliasDataProvider
     */
    public function testGetEntityAlias($entityClass, $expectedAlias)
    {
        $result = $this->entityAliasProvider->getEntityAlias($entityClass);
        $this->assertEntityAlias($expectedAlias, $result);
    }

    public function getEntityAliasDataProvider(): array
    {
        $translationNamespace = 'Oro\Bundle\EntityBundle\Tests\Unit\Provider\Fixtures\Translation';

        return [
            'excluded'                              => [
                'entityClass'   => 'Test\ExcludedEntity',
                'expectedAlias' => false
            ],
            'with_custom_alias'                     => [
                'entityClass'   => 'Test\EntityWithCustomAlias',
                'expectedAlias' => new EntityAlias('my_alias', 'my_plural_alias')
            ],
            'gedmo_translatable_entity'             => [
                'entityClass'   => $translationNamespace . '\GedmoTranslatableEntity',
                'expectedAlias' => false
            ],
            'gedmo_personal_translatable_entity'    => [
                'entityClass'   => $translationNamespace . '\GedmoPersonalTranslatableEntity',
                'expectedAlias' => false
            ],
            'bap_entity_eq_bundle_name'             => [
                'entityClass'   => 'Oro\Bundle\BarBundle\Entity\Bar',
                'expectedAlias' => new EntityAlias('bar', 'bars')
            ],
            'bap_entity_starts_with_bundle_name'    => [
                'entityClass'   => 'Oro\Bundle\BarBundle\Entity\BarType',
                'expectedAlias' => new EntityAlias('bartype', 'bartypes')
            ],
            'bap_entity'                            => [
                'entityClass'   => 'Oro\Bundle\BarBundle\Entity\Type',
                'expectedAlias' => new EntityAlias('type', 'types')
            ],
            'oro_entity_eq_bundle_name'             => [
                'entityClass'   => 'OroAPP\Bundle\BarBundle\Entity\Bar',
                'expectedAlias' => new EntityAlias('bar', 'bars')
            ],
            'oro_entity_starts_with_bundle_name'    => [
                'entityClass'   => 'OroAPP\Bundle\BarBundle\Entity\BarType',
                'expectedAlias' => new EntityAlias('bartype', 'bartypes')
            ],
            'oro_entity'                            => [
                'entityClass'   => 'OroAPP\Bundle\BarBundle\Entity\Type',
                'expectedAlias' => new EntityAlias('type', 'types')
            ],
            'vendor_entity_eq_bundle_name'          => [
                'entityClass'   => 'Acme\Bundle\BarBundle\Entity\Bar',
                'expectedAlias' => new EntityAlias('bar', 'bars')
            ],
            'vendor_entity_starts_with_bundle_name' => [
                'entityClass'   => 'Acme\Bundle\BarBundle\Entity\BarType',
                'expectedAlias' => new EntityAlias('bartype', 'bartypes')
            ],
            'vendor_entity'                         => [
                'entityClass'   => 'Acme\Bundle\BarBundle\Entity\Type',
                'expectedAlias' => new EntityAlias('bartype', 'bartypes')
            ],
            'other_entity'                          => [
                'entityClass'   => 'Test\Entity',
                'expectedAlias' => new EntityAlias('entity', 'entities')
            ]
        ];
    }

    private function assertEntityAlias($expected, $actual)
    {
        if ($expected instanceof EntityAlias) {
            $this->assertEquals($expected, $actual);
        } else {
            $this->assertSame($expected, $actual);
        }
    }
}
