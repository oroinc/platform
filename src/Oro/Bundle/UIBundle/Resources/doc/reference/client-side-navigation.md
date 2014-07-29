Client Side Navigation
======================

Client Side Navigation allows to load page in different formats HTML or JSON in depends on request. First request form browser loads complete HTML page. All following requests are made by JavaScript and load page blocks in JSON format.

Get ready page for client side navigation, we need to follow next steps:

- In main layout template additional check must be added, so it should look like:
```
{% if not oro_is_hash_navigation() %}
<!DOCTYPE html>
<html>
...
[content]
...
</html>
{% else %}
{# Template for hash tag navigation#}
{% include 'OroNavigationBundle:HashNav:hashNavAjax.html.twig'
    with {'script': block('head_script'), 'messages':block('messages'), 'content': block('page_container')}
%}
{% endif %}
```
where:
  `block('head_script')` - block with page related javascripts;
  `block('messages')` - block with system messages;
  `block('page_container')` - content area block (without header/footer), that will be realoaded during navigation

- To exclude links from processing with client side navigation (like windows open buttons, delete links), additional css class
"no-hash" should be added to the tag, e.g.
```
      <a href="page-url" class="no-hash">...</a>
```

As a part of navigation, form submit is also processed with Ajax.
