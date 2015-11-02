<?php

namespace Oro\Bundle\SecurityBundle\Formatter;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class ShareFormatter
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /**
     * @param TranslatorInterface $translator
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(TranslatorInterface $translator, AttachmentManager $attachmentManager)
    {
        $this->translator = $translator;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * Returns array of special entity data which is used, for example, in "Who has access" datagrid,
     * "oro_share_select" form type search result.
     *
     * @param object $object
     *
     * @return array
     */
    public function getEntityDetails($object)
    {
        $id = $label = $details = $image = $avatar = $classLabel = null;
        if ($object instanceof Organization) {
            $id = $object->getId();
            $label = $object->getName();
            $image = 'avatar-organization-small.png';
            $classLabel = $this->translator->trans('oro.organization.entity_label');
            $details = $classLabel;
        } elseif ($object instanceof BusinessUnit) {
            $id = $object->getId();
            $label = $object->getName();
            $image = 'avatar-business-unit-small.png';
            $classLabel = $this->translator->trans('oro.organization.businessunit.entity_label');
            $details = $classLabel
                . ' ' . $this->translator->trans('oro.security.datagrid.share_grid_row_details_from')
                . ' ' . $object->getOrganization()->getName();
        } elseif ($object instanceof User) {
            $id = $object->getId();
            $label = $object->getFirstName() . ' ' . $object->getLastName();
            $image = 'avatar-small.png';
            $classLabel = $this->translator->trans('oro.user.entity_label');
            $avatar = $object->getAvatar()
                ? $this->attachmentManager->getResizedImageUrl(
                    $object->getAvatar(),
                    AttachmentManager::SMALL_IMAGE_WIDTH,
                    AttachmentManager::SMALL_IMAGE_HEIGHT
                )
                : null;
            $details = $classLabel
                . ' ' . $this->translator->trans('oro.security.datagrid.share_grid_row_details_from')
                . ' ' . $object->getOwner()->getName();
        }

        return [
            'id' => $id,
            'label' => $label,
            'image' => $image,
            'avatar' => $avatar,
            'details' => $details,
            'classLabel' => $classLabel,
        ];
    }
}
