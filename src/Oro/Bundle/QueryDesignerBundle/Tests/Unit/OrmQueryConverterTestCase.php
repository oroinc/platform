<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\FunctionProviderInterface;

abstract class OrmQueryConverterTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param array $config
     *
     * @return VirtualFieldProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getVirtualFieldProvider(array $config = []): VirtualFieldProviderInterface
    {
        $provider = $this->createMock(VirtualFieldProviderInterface::class);
        $provider->expects(self::any())
            ->method('isVirtualField')
            ->willReturnCallback(function ($className, $fieldName) use (&$config) {
                $result = false;
                foreach ($config as $item) {
                    if ($item[0] === $className && $item[1] === $fieldName) {
                        $result = true;
                        break;
                    }
                }

                return $result;
            });
        $provider->expects(self::any())
            ->method('getVirtualFieldQuery')
            ->willReturnCallback(function ($className, $fieldName) use (&$config) {
                $result = [];
                foreach ($config as $item) {
                    if ($item[0] === $className && $item[1] === $fieldName) {
                        $result = $item[2];
                        break;
                    }
                }

                return $result;
            });

        return $provider;
    }

    /**
     * @param array $config
     *
     * @return VirtualRelationProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getVirtualRelationProvider(array $config = []): VirtualRelationProviderInterface
    {
        $provider = $this->createMock(VirtualRelationProviderInterface::class);
        $provider->expects(self::any())
            ->method('isVirtualRelation')
            ->willReturnCallback(function ($className, $fieldName) use ($config) {
                return !empty($config[$className][$fieldName]);
            });
        $provider->expects(self::any())
            ->method('getVirtualRelationQuery')
            ->willReturnCallback(function ($className, $fieldName) use ($config) {
                return $config[$className][$fieldName] ?? [];
            });
        $provider->expects(self::any())
            ->method('getTargetJoinAlias')
            ->willReturnCallback(function ($className, $fieldName) use ($config) {
                if (!empty($config[$className][$fieldName]['target_join_alias'])) {
                    return $config[$className][$fieldName]['target_join_alias'];
                }

                $joins = [];
                foreach ($config[$className][$fieldName]['join'] as $typeJoins) {
                    $joins = array_merge($joins, $typeJoins);
                }
                if (1 !== count($joins)) {
                    return null;
                }

                $join = reset($joins);

                return $join['alias'];
            });

        return $provider;
    }

    /**
     * @param array $config
     *
     * @return FunctionProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getFunctionProvider(array $config = []): FunctionProviderInterface
    {
        $provider = $this->createMock(FunctionProviderInterface::class);
        if (empty($config)) {
            $provider->expects(self::never())
                ->method('getFunction');
        } else {
            $provider->expects(self::any())
                ->method('getFunction')
                ->willReturnMap($config);
        }

        return $provider;
    }

    /**
     * @return EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getEntityNameResolver(): EntityNameResolver
    {
        return $this->createMock(EntityNameResolver::class);
    }

    /**
     * @param array $config            Example:
     *                                 'Test\Entity1' => array
     *                                 .    'column1'   => 'string',
     *                                 .    'relation1' => ['nullable' => true],
     *                                 'Test\Entity2' => array
     *                                 .    'column1' => 'integer',
     * @param array $identifiersConfig Example:
     *                                 'Test\Entity1' => ['id'],
     *                                 'Test\Entity2' => ['id'],
     *
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper(array $config = [], array $identifiersConfig = []): DoctrineHelper
    {
        return new DoctrineHelper($this->getDoctrine($config, $identifiersConfig));
    }

    /**
     * @param array $config            Example:
     *                                 'Test\Entity1' => array
     *                                 .    'column1'   => 'string',
     *                                 .    'relation1' => ['nullable' => true],
     *                                 'Test\Entity2' => array
     *                                 .    'column1' => 'integer',
     * @param array $identifiersConfig Example:
     *                                 'Test\Entity1' => ['id'],
     *                                 'Test\Entity2' => ['id'],
     *
     * @return ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getDoctrine(array $config = [], array $identifiersConfig = []): ManagerRegistry
    {
        $doctrine = $this->createMock(ManagerRegistry::class);

        $emMap = [];

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects(self::any())
            ->method('getDefaultQueryHints')
            ->willReturn([]);
        $configuration->expects(self::any())
            ->method('isSecondLevelCacheEnabled')
            ->willReturn(false);

        foreach ($config as $entity => $fields) {
            $em = $this->createMock(EntityManagerInterface::class);
            $em->expects(self::any())
                ->method('getConfiguration')
                ->willReturn($configuration);

            $emMap[] = [$entity, $em];

            $typeMap = [];
            $associationMap = [];
            foreach ($fields as $fieldName => $fieldType) {
                if (!is_array($fieldType)) {
                    $typeMap[] = [$fieldName, $fieldType];
                } else {
                    $associationMapValue = [$fieldName, ['joinColumns' => [$fieldType]]];
                    if (!empty($fieldType['type'])) {
                        $associationMapValue[1]['type'] = $fieldType['type'];
                    }
                    $associationMap[] = $associationMapValue;
                }
            }

            $metadata = $this->createMock(ClassMetadata::class);
            $metadata->expects(self::any())
                ->method('getIdentifierFieldNames')
                ->willReturn($identifiersConfig[$entity] ?? []);
            $metadata->expects(self::any())
                ->method('getIdentifier')
                ->willReturn($identifiersConfig[$entity] ?? []);
            $metadata->expects(self::any())
                ->method('getTypeOfField')
                ->willReturnMap($typeMap);
            if ($associationMap) {
                $metadata->expects(self::any())
                    ->method('hasAssociation')
                    ->willReturn(true);
                $metadata->expects(self::any())
                    ->method('getAssociationMapping')
                    ->willReturnMap($associationMap);
            } else {
                $metadata->expects(self::any())
                    ->method('hasAssociation')
                    ->willReturn(false);
            }

            $em->expects(self::any())
                ->method('getClassMetadata')
                ->with($entity)
                ->willReturn($metadata);
        }

        if (!empty($emMap)) {
            $doctrine->expects(self::any())
                ->method('getManagerForClass')
                ->willReturnMap($emMap);
        }

        return $doctrine;
    }
}
