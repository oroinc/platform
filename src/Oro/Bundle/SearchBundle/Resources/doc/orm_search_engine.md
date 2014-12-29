ORM search engine
=================

OroSearchBundle provides ORM search engine out of the box. It stores index data in DB tables and uses fulltext
index to perform search. Bundle supports search index for both **MySQL** and **PostgreSQL** DBMS. ORM engine is used by default.

Configuration
-------------

ORM engine configuration stored at `Oro/Bundle/SearchBundle/Resources/config/oro/search_engine/orm.yml`
and do not require any additional engine parameters.

ORM search engine has quite straightforward implementation - it simply stores index data in appropriate tables:
separate tables for `text`, `datetime`, `decimal` and `integer` value, and another one table to store general information.
Table that stores text data has `fulltext` index.

```yml
parameters:
    oro_search.engine.class: Oro\Bundle\SearchBundle\Engine\Orm

services:
    oro_search.search.engine:
        class: %oro_search.engine.class%
        arguments:
            - @doctrine
            - @oro_entity.doctrine_helper
            - @oro_search.mapper
        calls:
            - [setLogQueries, [%oro_search.log_queries%]]
            - [setDrivers, [%oro_search.drivers%]]
```

Each supported DBMS has it's own driver that knows about specific search implementation and generates valid SQL.

```yml
parameters:
    oro_search.drivers:
        pdo_mysql: Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql
        pdo_pgsql: Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql
```

Features
--------

ORM search engine overrides index listener class with it's own implementation
_Oro\Bundle\SearchBundle\EventListener\OrmIndexListener_. This listener disables multiple flushes
for save and delete operation and run only one flush instead. Also it can be temporary disabled that allows to
perform operation with big data faster.

Another one feature of ORM engine is fulltext index processing. Configuration defines fulltext manager
_Oro\Bundle\SearchBundle\Engine\FulltextIndexManager_ that used during installation and inside special listener -
it allows system to create fulltext indexes bypassing Doctrine processing.

**Note for MySQL driver:** MySQL has lower limit to the string length for fulltext index called
[ft_min_word_len](http://dev.mysql.com/doc/refman/5.1/en/server-system-variables.html#sysvar_ft_min_word_len),
i.e. if string will be shorter than this limit then fulltext index will not be used. It's recommended
to [change this value to 3](http://dev.mysql.com/doc/refman/5.1/en/fulltext-fine-tuning.html).
