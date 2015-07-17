Permissions in ActivityList bundle
==================================

At the moment each activity entity must contains provider (for example EmailActivityListProvider) with implemented
interface ActivityListProviderInterface. Method ActivityList::getActivityOwners returns one or many ActivityOwner
entities which connected with their activity list entity.