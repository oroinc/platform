<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributesDatagridListener;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AttributesDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $urlGenerator;

    /** @var AttributesDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->listener = new AttributesDatagridListener(
            $this->doctrineHelper,
            $this->localizationHelper,
            $this->aclHelper,
            $this->authorizationChecker,
            $this->urlGenerator
        );
    }

    public function testOnResultAfter()
    {
        $resultRecord1 = new ResultRecord(['id' => 1]);
        $resultRecord2 = new ResultRecord(['id' => 2]);
        $resultRecord5 = new ResultRecord(['id' => 5]);

        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [$resultRecord1, $resultRecord2, $resultRecord5]
        );

        $repository = $this->createMock(AttributeGroupRelationRepository::class);
        $label1 = new LocalizedFallbackValue();
        $label1->setString('family1');
        $label2 = new LocalizedFallbackValue();
        $label2->setString('family2');
        $this->localizationHelper->expects($this->exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive(
                [new ArrayCollection([$label1])],
                [new ArrayCollection([$label2])]
            )
            ->willReturnOnConsecutiveCalls(
                $label1,
                $label2
            );

        $families = [
            2 => [
                ['id' => 1, 'labels' => new ArrayCollection([$label1])],
                ['id' => 2, 'labels' => new ArrayCollection([$label2])]
            ],
            5 => [['id' => 2, 'labels' => new ArrayCollection([$label2])]]
        ];

        $repository->expects($this->once())
            ->method('getFamiliesLabelsByAttributeIdsWithAcl')
            ->with([1, 2, 5], $this->aclHelper)
            ->willReturn($families);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(AttributeGroupRelation::class)
            ->willReturn($repository);

        $this->authorizationChecker->expects($this->exactly(2))
            ->method('isGranted')
            ->withConsecutive(
                ['oro_attribute_family_view', new ObjectIdentity(1, AttributeFamily::class)],
                ['oro_attribute_family_view', new ObjectIdentity(2, AttributeFamily::class)]
            )
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('oro_attribute_family_view', ['id' => 1])
            ->willReturn('view_link');

        $this->listener->onResultAfter($event);

        $expectedRecord1 = new ResultRecord(['id' => 1]);
        $expectedRecord2 = new ResultRecord(['id' => 2]);
        $expectedRecord5 = new ResultRecord(['id' => 5]);
        $expectedRecord1->setValue('attributeFamiliesViewData', []);
        $expectedRecord2->setValue(
            'attributeFamiliesViewData',
            [
                ['viewLink' => 'view_link', 'label' => 'family1'],
                ['viewLink' => null, 'label' => 'family2']
            ]
        );
        $expectedRecord5->setValue('attributeFamiliesViewData', [['viewLink' => null, 'label' => 'family2']]);
        $expectedRecords = [
            $expectedRecord1,
            $expectedRecord2,
            $expectedRecord5
        ];

        $this->assertEquals($expectedRecords, $event->getRecords());
    }
}
