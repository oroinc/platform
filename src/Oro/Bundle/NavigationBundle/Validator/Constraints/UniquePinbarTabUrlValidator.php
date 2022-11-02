<?php

namespace Oro\Bundle\NavigationBundle\Validator\Constraints;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NavigationBundle\Entity\AbstractNavigationItem;
use Oro\Bundle\NavigationBundle\Entity\AbstractPinbarTab;
use Oro\Bundle\NavigationBundle\Entity\Repository\PinbarTabRepository;
use Oro\Bundle\NavigationBundle\Exception\LogicException;
use Oro\Bundle\NavigationBundle\Utils\PinbarTabUrlNormalizerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Ensures that PinbarTab URL is unique for each user.
 */
class UniquePinbarTabUrlValidator extends ConstraintValidator
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var PinbarTabUrlNormalizerInterface */
    private $pinbarTabUrlNormalizer;

    public function __construct(DoctrineHelper $doctrineHelper, PinbarTabUrlNormalizerInterface $pinbarTabUrlNormalizer)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->pinbarTabUrlNormalizer = $pinbarTabUrlNormalizer;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $entity
     * @param UniquePinbarTabUrl $constraint
     */
    public function validate($entity, Constraint $constraint)
    {
        if (!$entity instanceof AbstractPinbarTab) {
            return;
        }

        /** @var AbstractNavigationItem $navigationItem */
        $navigationItem = $entity->getItem();
        if (!$navigationItem) {
            throw new LogicException('PinbarTab does not contain NavigationItem');
        }

        $normalizedUrl = $this->pinbarTabUrlNormalizer->getNormalizedUrl($navigationItem->getUrl());

        /** @var PinbarTabRepository $pinbarTabRepository */
        $pinbarTabRepository = $this->doctrineHelper->getEntityRepositoryForClass($constraint->pinbarTabClass);

        $sameNavigationItemsCount = $pinbarTabRepository->countNavigationItems(
            $normalizedUrl,
            $navigationItem->getUser(),
            $navigationItem->getOrganization(),
            $navigationItem->getType()
        );

        if ($sameNavigationItemsCount > 0) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
