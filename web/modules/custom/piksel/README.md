# Media piksel

This module provides a new media source that can be used to import piksel
media as local media items using the media library. piksel sources for
images can be added using plugins. The module ships with plugins for the open
source image libraries [Pexels](https://pexels.com) and
[Unsplash](https://unsplash.com).

## Using the module
- Configure the API keys for Pexels and/or Unsplash using `settings.local.php`.
```
$settings['media.piksel_provider.unsplash.access_key'] = 'YOUR_UNSPLASH_ACCESS_KEY';
$settings['media.piksel_provider.pexels.api_key'] = 'YOUR_PEXELS_API_KEY';
```
- Create a new media type choosing "piksel media" as the source.
- In the source configuration, select the provider plugin you want to use for
  the media type.
- Save the media type, the source field that stores the piksel media ID will
  automatically be created.
- To improve performance, it is recommended to create extra custom fields to
  store API data.
  - Go to the "Manage fields" page and create a separate text field to store
    the image URL.
  - Go to the "Manage fields" page and create a separate text field to store
    the image alt text.
  - Edit the media type and map the "File URL" and "Alt text" field to the
    created custom fields.
  - Since the custom fields are now automatically filled when media items are
    created, it is fine to remove them from the different form displays. The
    alt text can still be shown if editors prefer to override the alt texts
    provides by the piksel media provider.
  - Using the imagecache_piksel module, you can apply an image style to the
    custom text field containing the mapped image URL.
