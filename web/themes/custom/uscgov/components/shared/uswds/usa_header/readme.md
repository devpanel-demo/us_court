# How to use

See documentation at https://designsystem.digital.gov/components/header/

## 1. Add the following to your `page.html.twig`

```twig
  {% embed 'uscgov:header' with {
    extended: true,
    attributes,
    branding: page.header.uscgov_site_branding,
    main_menu: page.header.uscgov_main_menu,
    secondary_menu: page.header.uscgov_useraccountmenu,
    search_box: page.header.uscgov_search_form_narrow,
  } only %}
    {% block branding %}
      {{ branding }}
    {% endblock %}
    {% block main_menu %}
      {{ main_menu }}
    {% endblock %}
    {% block secondary_menu %}
      {{ secondary_menu }}
    {% endblock %}
    {% block search_box %}
      {{ search_box }}
    {% endblock %}
  {% endembed %}
```

## 2. Ensure that the following blocks are placed within the `header` region of your
theme:
- Site branding
- Main navigation
- User account menu
- Search form

## 3. Ensure each of those blocks' templates is utilizing their respective SDC components.
- Site branding (`block--system-branding-block.html.twig`) - header_navbar
- Main navigation (`menu--main.html.twig`) - main_menu
- Secondary navigation (`menu--account.html.twig`) - secondary_menu
- Search box (`form--search-form.html.twig`) - search
