<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Adds family data to attributes grid for the current result set.
 */
class AttributesDatagridListener
{
    private const ATTRIBUTE_FAMILY_VIEW_ROUTE = 'oro_attribute_family_view';
    private const ATTRIBUTE_FAMILY_PERMISSION = 'oro_attribute_family_view';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /** @var AclHelper */
    private $aclHelper;

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        LocalizationHelper $localizationHelper,
        AclHelper $aclHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->localizationHelper = $localizationHelper;
        $this->aclHelper = $aclHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->urlGenerator = $urlGenerator;
    }

    public function onResultAfter(OrmResultAfter $event)
    {
        $attributeIds = [];
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        foreach ($records as $record) {
            $attributeIds[] = $record->getValue('id');
        }

        /** @var AttributeGroupRelationRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(AttributeGroupRelation::class);

        $families = $repository->getFamiliesLabelsByAttributeIdsWithAcl($attributeIds, $this->aclHelper);
        $familyIsGranted = [];
        $familyViewUrls = [];
        $familyLabels = [];
        foreach ($records as $record) {
            $familyData = array_map(function (array $familyDataRow) use (
                &$familyIsGranted,
                &$familyViewUrls,
                &$familyLabels
            ) {
                $familyId = $familyDataRow['id'];
                if (!array_key_exists($familyId, $familyIsGranted)) {
                    $familyIsGranted[$familyId] = $this->authorizationChecker->isGranted(
                        self::ATTRIBUTE_FAMILY_PERMISSION,
                        new ObjectIdentity($familyId, AttributeFamily::class)
                    );
                }
                if ($familyIsGranted[$familyId] && !array_key_exists($familyId, $familyViewUrls)) {
                    $familyViewUrls[$familyId] = $this->urlGenerator->generate(
                        self::ATTRIBUTE_FAMILY_VIEW_ROUTE,
                        ['id' => $familyId]
                    );
                }
                if (!array_key_exists($familyId, $familyLabels)) {
                    $familyLabels[$familyId] = $this->localizationHelper
                        ->getLocalizedValue($familyDataRow['labels'])
                        ->getString();
                }

                return [
                    'viewLink' => $familyViewUrls[$familyId] ?? null,
                    'label' => $familyLabels[$familyId]
                ];
            }, $families[$record->getValue('id')] ?? []);

            $record->setValue('attributeFamiliesViewData', $familyData);
        }
    }
}
