const { blocks, data, element, components, blockEditor } = wp;
const { registerBlockType } = blocks;
const { dispatch, select } = data;
const { Fragment, useState } = element;
const { PanelBody, Button, Icon, RangeControl, IconButton, Toolbar, SelectControl } = components;
const { MediaUpload, MediaUploadCheck, BlockControls } = blockEditor;

const __ = Drupal.t;

const gallerySettings = {
  title: 'Gallery',
  category: 'uswds',
  icon: 'format-gallery',
  attributes: {
    medias: {
      type: 'array',
      default: [],
    },
  },
  edit: function (props) {
    const { attributes, setAttributes, isSelected } = props;
    const { medias } = attributes;

    const [selectedMediaId, setSelectedMediaId] = useState(null);

    const getEntity = async (item) => {
      const response = await fetch(
        Drupal.url(`gutenberg/media/load-media/${item}`),
      );

      if (response.ok) {
        const mediaEntity = await response.json();

        if (mediaEntity) {
          return mediaEntity;
        }
      }

      if (response.status === 404) {
        Drupal.notifyError("Media entity couldn't be found.");
        return null;
      }

      if (!response.ok) {
        Drupal.notifyError('An error occurred while fetching data.');
        return null;
      }
    };

    const onSelectImages = async (newMedia) => {
      // Define the async function to process each media item
      const processMediaItem = async (media) => {
        if (media.media_entity.bundle === 'piksel_program') {
          // Replace this with your actual async processing function
          const moreMediaInfo = await getEntity(media.media_entity.id);
          media.thumbnail_uri = moreMediaInfo.thumbnail_uri;
          media.label = media.media_entity.label;
        }

        if (media.media_entity.bundle === 'image') {
          media.label = media.alt;
        }

        if (media.media_entity.bundle === 'remote_video') {
          media.label = media.title;
        }

          return media;
      };

      // Use Promise.all to process all new media items asynchronously
      const processedNewMedia = await Promise.all(newMedia.map(processMediaItem));

      // Combine existing media with the processed new media, avoiding duplicates
      const existingMediaIds = medias.map(media => media.media_entity.id);
      const combinedMedia = [
        ...medias,
        ...processedNewMedia.filter(media => !existingMediaIds.includes(media.media_entity.id)),
      ];

      // Update attributes with the new media list
      setAttributes({ medias: combinedMedia });
    };

    const removeMedia = () => {
      if (selectedMediaId) {
        // Filter out the media with the selected ID
        const newMedias = medias.filter(media => media.media_entity.id !== selectedMediaId);
        setAttributes({ medias: newMedias });
        setSelectedMediaId(null);
      }
    };

    const selectMediaForRemoval = (mediaId) => {
      setSelectedMediaId(mediaId);
    };

    return (
      <>
        <BlockControls>
          {/* Conditionally render the Remove button if a media item is selected */}
          {selectedMediaId && (
            <Button
              className="components-toolbar__control"
              onClick={removeMedia}
            >
              {__('Remove Selected Media')}
            </Button>
          )}
        </BlockControls>

        {isSelected &&
          <MediaUploadCheck>
            <MediaUpload
              onSelect={onSelectImages}
              allowedBundles={['image', 'remote_video', 'piksel_program']}
              multiple
              gallery
              value={medias.map(img => img.id)}
              render={({ open }) => (
                <Button onClick={open} className="button button-large">
                  {__('Select Media')}
                </Button>
              )}
            />
          </MediaUploadCheck>
        }

        <div className="usc-gallery">
          <div className="main-slider">
            <div className="splide__arrows">
              <button
                className="button button--arrow button--arrow-back splide__arrow splide__arrow--prev">
              </button>
              <button
                className="button button--arrow splide__arrow splide__arrow--next">
              </button>
            </div>

            <div className="splide__track">
              <ul className="splide__list">
                {medias.map((media) => (
                  <li className="splide__slide is-image-slide" key={media.media_entity.id}>
                    <div className="slide-content-wrapper">
                      <div className="splide__slide__container">
                        <img
                          src={media.media_entity.bundle === 'image' ? media.media_details.sizes['gallery_thumb'].source_url : media.thumbnail_uri.replace('public://', '/sites/default/files/')}
                          width="350"
                          height="350"
                          alt=""
                          className="image-style-gallery-thumb"
                          onClick={() => selectMediaForRemoval(media.media_entity.id)}
                          style={{ cursor: 'pointer', border: selectedMediaId === media.media_entity.id ? '2px solid red' : 'none' }}
                        />
                      </div>
                      <div className="details-box">
                        {media.label}
                      </div>
                    </div>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </>
    );
  },
  save: function (props) {
    const { attributes } = props;
    const { medias } = attributes;

    return (
      <></>
    );
  },
};

const category = {
  slug: 'uswds',
  title: __('USWDS'),
};

const currentCategories = select('core/blocks').getCategories().filter(item => item.slug !== category.slug);
dispatch('core/blocks').setCategories([category, ...currentCategories]);

registerBlockType(`${category.slug}/gallery`, { category: category.slug, ...gallerySettings });
