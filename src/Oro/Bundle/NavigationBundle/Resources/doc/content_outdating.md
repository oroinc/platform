Content outdating
=================

Table of content
-----------------
- [Overview](#overview)
- [Frontend implementation](#frontend-implementation)
- [Backend implementation](#backend-implementation)

Overview
---------

As user I want to know if opened page or page that comes from local cache(JS when page is pinned) was changed.
To achieve this goal we should notify client application when content is changed ASAP. To improve timings decided to use websocket pub/sub protocol for pushing notifications to client.

Frontend implementation
-----------------------

To cover requirements content manager object was implemented. Responsibility of this object is to store cached content and tags related to content (for just loaded or cached as well).
Hash navigation responsible for caching content, and templates are responsible for content tagging.
If your template based on base templates of UIBundle (such as action/index.html.twig, action/update.html.twig)
your content will be tagged automatically, in cases when tags should be added dynamically content manager could be used directly.

**Example**
``` twig
    <script type="text/javascript">
        require(['oro/content-manager'],
        function(contentManager) {
            contentManager.tagContent(['users']);
        });
    </script>
```
In case when your template extends base template, but content depends on some additional objects you can use macros that defined in `NavigationBundle/Resources/views/Include/contentTags.html.twig`

**Example**
``` twig
    {% block navigation_content_tags %}
        {# Main entity here #}
        {{ navigationMacro.navigationContentTags(entity) }}

        {# Additional entity here #}
        {{ navigationMacro.navigationContentTags(someAdditionalEntity) }}
    {% endblock %}
```

Backend implementation
----------------------

For doctrine entities tag generation are covered in onFlush event listener. For each entity modified into UnitOfWork `TagGeneratorChain#generate` method will be invoked.
 To add your own generator into chain you should develop a class that implements `TagGeneratorInterface` and register it as service with `oro_navigation.tag_generator` tag.
