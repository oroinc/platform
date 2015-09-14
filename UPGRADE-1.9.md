UPGRADE FROM 1.8 to 1.9
=======================

####ActivityListBundle
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setEditor` and `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getEditor` methods marked as deprecated in favor of new `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setUpdatedBy` and `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getUpdatedBy`.
- `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getDate` is deprecated. Instead use getCreatedAt and getUpdatedAt
