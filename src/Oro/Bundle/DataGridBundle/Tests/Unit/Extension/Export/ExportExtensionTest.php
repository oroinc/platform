<?php
namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Export;

use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Extension\Export\ExportExtension;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ExportExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testShouldBeConstructedWithRequiredAttributes()
    {
        $extension = new ExportExtension(
            $this->getTranslatorMock(),
            $this->getSecurityFacadeMock()
        );

        $this->assertInstanceOf(AbstractExtension::class, $extension);
    }

    public function testShouldThrowExceptionIfIdsExistAndDatasourceIsNotInstanceOfOrmDatasource()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessageRegExp('/Expected argument of type .*? given/');

        $extension = new ExportExtension(
            $this->getTranslatorMock(),
            $this->getSecurityFacadeMock()
        );

        $extension->setParameters(new ParameterBag(['_export' => ['ids' => [1,2]]]));
        $datagridConfiguration = $this->getDatagridConfigurationMock();
        $datasource = $this->getDatasourceMock();
        $extension->visitDatasource($datagridConfiguration, $datasource);
    }

    public function testShouldAddWhereToQueryBuilder()
    {
        $extension = new ExportExtension(
            $this->getTranslatorMock(),
            $this->getSecurityFacadeMock()
        );

        $extension->setParameters(new ParameterBag(['_export' => ['ids' => [1,2]]]));
        $datagridConfiguration = $this->getDatagridConfigurationMock();

        $metadata = $this->getClassMetadataMock();
        $metadata
            ->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $em = $this->getEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with('Test')
            ->willReturn($metadata);

        $qb = $this->getQueryBuilderMock();
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

        $datasource = $this->getOrmDatasourceMock();
        $datasource
            ->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($qb);

        $extension->visitDatasource($datagridConfiguration, $datasource);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private function getEntityManagerMock()
    {
        return $this->createMock(EntityManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ClassMetadata
     */
    private function getClassMetadataMock()
    {
        return $this->createMock(ClassMetadata::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    private function getTranslatorMock()
    {
        return $this->createMock(TranslatorInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridConfiguration
     */
    private function getDatagridConfigurationMock()
    {
        return $this->createMock(DatagridConfiguration::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueryBuilder
     */
    private function getQueryBuilderMock()
    {
        return $this->createMock(QueryBuilder::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DatasourceInterface
     */
    private function getDatasourceMock()
    {
        return $this->createMock(DatasourceInterface::class);
    }
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|OrmDatasource
     */
    private function getOrmDatasourceMock()
    {
        return $this->createMock(OrmDatasource::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    private function getSecurityFacadeMock()
    {
        return $this->createMock(SecurityFacade::class);
    }
}
