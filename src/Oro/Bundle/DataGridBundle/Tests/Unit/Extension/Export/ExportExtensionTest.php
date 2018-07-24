<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Export;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ExportExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldBeConstructedWithRequiredAttributes()
    {
        $extension = new ExportExtension(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(TokenStorageInterface::class)
        );

        $this->assertInstanceOf(AbstractExtension::class, $extension);
    }

    public function testShouldThrowExceptionIfIdsExistAndDatasourceIsNotInstanceOfOrmDatasource()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessageRegExp('/Expected argument of type .*? given/');

        $extension = new ExportExtension(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(TokenStorageInterface::class)
        );

        $extension->setParameters(new ParameterBag(['_export' => ['ids' => [1,2]]]));
        $datagridConfiguration = $this->createMock(DatagridConfiguration::class);
        $datasource = $this->createMock(DatasourceInterface::class);
        $extension->visitDatasource($datagridConfiguration, $datasource);
    }

    public function testShouldAddWhereToQueryBuilder()
    {
        $extension = new ExportExtension(
            $this->createMock(TranslatorInterface::class),
            $this->createMock(AuthorizationCheckerInterface::class),
            $this->createMock(TokenStorageInterface::class)
        );

        $extension->setParameters(new ParameterBag(['_export' => ['ids' => [1,2]]]));
        $datagridConfiguration = $this->createMock(DatagridConfiguration::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata
            ->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $em = $this->createMock(EntityManager::class);
        $em
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with('Test')
            ->willReturn($metadata);

        $qb = $this->createMock(QueryBuilder::class);
        $qb
            ->expects($this->once())
            ->method('getRootAliases')
            ->willReturn(['o']);
        $qb
            ->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);
        $qb
            ->expects($this->once())
            ->method('getRootEntities')
            ->willReturn(['Test']);
        $qb
            ->expects($this->once())
            ->method('andWhere')
            ->with('o.id IN (:exportIds)')
            ->willReturn($qb);
        $qb
            ->expects($this->once())
            ->method('setParameter')
            ->with('exportIds', [1,2]);

        $datasource = $this->createMock(OrmDatasource::class);
        $datasource
            ->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $extension->visitDatasource($datagridConfiguration, $datasource);
    }
}
