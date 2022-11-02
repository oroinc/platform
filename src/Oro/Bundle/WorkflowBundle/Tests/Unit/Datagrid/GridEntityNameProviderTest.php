<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Datagrid;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Datagrid\GridEntityNameProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class GridEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var GridEntityNameProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new GridEntityNameProvider(
            $this->configProvider,
            $this->em,
            $this->translator
        );
        $this->provider->setEntityName('test');
    }

    public function testGetRelatedEntitiesChoiceConfigurable()
    {
        $entity = \stdClass::class;
        $label = 'Test';

        $result = [['relatedEntity' => $entity]];

        $qb = $this->assertResultCall($result);
        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(true);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn('untranslated.label');
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with($entity)
            ->willReturn($config);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('untranslated.label')
            ->willReturn($label);

        $expected = [$label => $entity];
        $this->assertEquals($expected, $this->provider->getRelatedEntitiesChoice());
    }

    public function testGetRelatedEntitiesChoiceNotConfigurable()
    {
        $entity = \stdClass::class;

        $result = [['relatedEntity' => $entity]];

        $qb = $this->assertResultCall($result);
        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with($entity)
            ->willReturn(false);

        $this->configProvider->expects($this->never())
            ->method('getConfig');
        $this->translator->expects($this->never())
            ->method('trans');

        $expected = [$entity => $entity];
        $this->assertEquals($expected, $this->provider->getRelatedEntitiesChoice());
    }

    /**
     * @return QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertResultCall(array $result)
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getArrayResult')
            ->willReturn($result);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('select')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('from')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('distinct')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        return $qb;
    }
}
