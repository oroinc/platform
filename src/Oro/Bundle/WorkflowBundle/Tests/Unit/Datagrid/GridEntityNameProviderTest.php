<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Datagrid\GridEntityNameProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class GridEntityNameProviderTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private ConfigProvider&MockObject $configProvider;
    private TranslatorInterface&MockObject $translator;
    private GridEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($this->em);

        $this->provider = new GridEntityNameProvider(
            $this->configProvider,
            $doctrine,
            $this->translator
        );
        $this->provider->setEntityName('test');
    }

    public function testGetRelatedEntitiesChoiceConfigurable(): void
    {
        $entity = \stdClass::class;
        $label = 'Test';

        $result = [['relatedEntity' => $entity]];

        $qb = $this->assertResultCall($result);
        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->configProvider->expects(self::once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(true);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())
            ->method('get')
            ->with('label')
            ->willReturn('untranslated.label');
        $this->configProvider->expects(self::once())
            ->method('getConfig')
            ->with($entity)
            ->willReturn($config);
        $this->translator->expects(self::once())
            ->method('trans')
            ->with('untranslated.label')
            ->willReturn($label);

        $expected = [$label => $entity];
        self::assertEquals($expected, $this->provider->getRelatedEntitiesChoice());
    }

    public function testGetRelatedEntitiesChoiceNotConfigurable(): void
    {
        $entity = \stdClass::class;

        $result = [['relatedEntity' => $entity]];

        $qb = $this->assertResultCall($result);
        $this->em->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->configProvider->expects(self::once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(false);

        $this->configProvider->expects(self::never())
            ->method('getConfig');
        $this->translator->expects(self::never())
            ->method('trans');

        $expected = [$entity => $entity];
        self::assertEquals($expected, $this->provider->getRelatedEntitiesChoice());
    }

    private function assertResultCall(array $result): QueryBuilder&MockObject
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn($result);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects(self::once())
            ->method('select')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('from')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('distinct')
            ->willReturnSelf();
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        return $qb;
    }
}
