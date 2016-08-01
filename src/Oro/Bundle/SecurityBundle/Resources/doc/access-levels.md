Access levels
========

Access levels allow to protect database records.

There are 6 access levels:

 - **System**: Allows to gives a user a permissions to access to all records within the system.
 - **Organization**: Allows to gives a user a permissions to access to all records within the organization, regardless of the business unit hierarchical level to which a record belongs or the user is assigned to.
 - **Division**: Allows to gives a user a permissions to access to records in all business units are assigned to the user and all business units subordinate to business units are assigned to the user.
 - **Business Unit**: Allows to gives a user a permissions to access to records in all business units are assigned to the user.
 - **User**: Allows to gives a user a permissions to access to own records and records that are shared with the user.
 - **None**: Access denied.

 All the records have additional owner parameter - organization. Then user log into the system, he works in scope of one organization.

 If the record was created in the scope of first organization, user can see this record only with system access level to this entity.

[Examples](./examples.md)
  
There are several ways to protect the records with access levels.

###Data grids protections.

All records in datagrids automatically protect with access levels. Developer doesn't need turn on the protection manually.

Now it protects view permission for records.

###Protection with Param Converters.

When a developer uses Sensio Param Converter in controller's actions and this action has ACL annotation an additional security check will be run for the input parameters.

If input parameters for this action contain a doctrine entity object whose class name was described in the ACL annotation, the permissions for this object will be checked against the ACL annotation.

If Param converter ACL access level check can't protect the entity, then was turn on protection on class level from action ACL annotation.

###Manual protection of select queries.

Developers can protect select DQL in QueryBuilder or Query with oro_security.acl_helper service:

``` php
$repository = $this->getDoctrine()
   ->getRepository('AcmeDemoBundle:Product');
$queryBuilder = $repository->createQueryBuilder('p')
   ->where('p.price > :price')
   ->setParameter('price', '19.99')
   ->orderBy('p.price', 'ASC');
   
$query = $this->get('oro_security.acl_helper')->apply($queryBuilder, 'VIEW');
```

As result, $query will be marked as ACL protected and it will automatically delete records that user doesn't have permission.

###Manual access check on object.

Developer can check access to the given entity record by using isGranted method of Security facade service:

``` php
$entity = $repository->findOneBy('id' => 10);

if (!$this->securityFacade->isGranted('VIEW', $entity)) {
    throw new AccessDeniedException('Access denied');
} else {
    // access is granted
}  
```

###Manual access check on object field.

Developer can check access to the given entity record by using isGranted method of Security facade service:

``` php
$entity = $repository->findOneBy('id' => 10);

if (!$this->securityFacade->isGranted('VIEW', new FieldVote($entity, '_field_name_'))) {
    throw new AccessDeniedException('Access denided');
} else {
    // access is granted
}  
```

###Check ACL for search queries

During collecting entities to search, information about owner and organization will be added to search index automatically.

Every search query is ACL protected with Search ACL helper. This helper limit data with current access levels for entities which are used in the query.
