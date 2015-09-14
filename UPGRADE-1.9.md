UPGRADE FROM 1.8 to 1.9
=======================

####ActivityListBundle
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setEditor` deprecated since 1.8.0. Will be removed in 1.10.0. Use `Oro\Bundle\ActivityListBundle\Entity\ActivityList::setUpdatedBy` instead. 
- `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getEditor` deprecated since 1.8.0. Will be removed in 1.10.0. Use `Oro\Bundle\ActivityListBundle\Entity\ActivityList::getUpdatedBy` instead.
- `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getDate` deprecated since 1.8.0. Will be removed in 1.10.0. Instead use `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getCreatedAt` and `Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface::getUpdatedAt` instead
