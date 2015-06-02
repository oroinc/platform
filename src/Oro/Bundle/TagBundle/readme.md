OroTagBundle
============

Entities categorization with tags.


Tags in grids.
--------------

In case if entity implements Taggable interface, at the grid of this entity will be automatically add tags filter. Tags list in this filter will be
limited by access level and was taken only tags from this entity.


Tags in report builder.
-----------------------

User can create reports and use tags from for related entity. If entity implements Taggable interface, in fields list for
this entity will be available virtual relation `tags`.
