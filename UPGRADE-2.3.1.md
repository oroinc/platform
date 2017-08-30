UPGRADE FROM 2.3 to 2.3.1
=========================

ElasticSearchBundle
-------------------
- Tokenizer configuration has been changed. A full rebuilding of the backend search index is required.

SegmentBundle
-------------
- Class Oro\Bundle\SegmentBundle\Query\SegmentQueryConverterFactory was created. It was registered as the service `oro_segment.query.segment_query_converter_factory`.
    services.yml
    ```yml
    oro_segment.query.segment_query_converter_factory:
        class: 'Oro\Bundle\SegmentBundle\Query\SegmentQueryConverterFactory'
        arguments:
            - '@oro_query_designer.query_designer.manager'
            - '@oro_entity.virtual_field_provider.chain'
            - '@doctrine'
            - '@oro_query_designer.query_designer.restriction_builder'
            - '@oro_entity.virtual_relation_provider.chain'
        public: false
    ```
- Service `oro_segment.query.segment_query_converter_factory.link` was created to initialize the service `oro_segment.query.segment_query_converter_factory` in `Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder`.
    services.yml
    ```yml
    oro_segment.query.segment_query_converter_factory.link:
        tags:
            - { name: oro_service_link,  service: oro_segment.query.segment_query_converter_factory }
    ```
- Class `Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder` was changed to use service `oro_segment.query.segment_query_converter_factory.link` instead of `oro_segment.query_converter.segment.link`.
    - public method `setSegmentQueryConverterFactoryLink(ServiceLink $segmentQueryConverterFactoryLink)` was added.
- Definition of service `oro_segment.query.dynamic_segment.query_builder` was changed in services.yml.
    Before
    ```yml
    oro_segment.query.dynamic_segment.query_builder:
        class: %oro_segment.query.dynamic_segment.query_builder.class%
        arguments:
            - '@oro_segment.query_converter.segment.link'
            - '@doctrine'
    ```
    After
    ```yml
    oro_segment.query.dynamic_segment.query_builder:
        class: %oro_segment.query.dynamic_segment.query_builder.class%
        arguments:
            - '@oro_segment.query_converter.segment.link'
            - '@doctrine'
        calls:
            - [setSegmentQueryConverterFactoryLink, ['@oro_segment.query.segment_query_converter_factory.link']]
    ```
