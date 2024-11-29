<?php

namespace Drupal\piksel\Form;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\media_library\Form\AddFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Creates a form to find and add piksel media items.
 */
class PikselAddForm extends AddFormBase {

  /**
   * The piksel media search service.
   *
   * @var \Drupal\piksel\PikselSearch
   */
  protected $pikselSearch;

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public static function create(ContainerInterface $container) {
    $instance = new static(
      $container->get('entity_type.manager'),
      $container->get('media_library.ui_builder'),
      $container->get('media_library.opener_resolver')
    );
    $instance->pikselSearch = $container->get('piksel.search');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->getBaseFormId() . '_piksel_media';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    if ($form_state->get('keyword') && empty($this->getAddedMediaItems($form_state))) {
      $form['#prefix'] = '<div id="media-library-content" class="media-library-content"><div id="media-library-add-form-wrapper">';
      $form['#suffix'] = '</div></div>';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  protected function buildInputElement(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="media-piksel-library">';
    $form['#suffix'] = '</div>';
    $form['#attributes']['class'][] = 'media-library-add-form--piksel-media';
    $form['#attributes']['class'][] = 'js-media-library-add-form-piksel-media';

    // Attach our custom library for the import selection.
    $form['#attached']['library'][] = 'piksel/ui';

    // Add a container to group the input elements for styling purposes.
    $form['input_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'media-library-add-form__input-wrapper',
        ],
      ],
    ];

    $provider_name = $this->getMediaType($form_state)->getSource()->getConfiguration()['provider'];
    $options = $this->pikselSearch->searchProjects($provider_name);
    $projects_list = [];
    foreach ($options as $key => $project) {
      $projects_list[$key] = $project['title'];
    }

    $form['input_wrapper']['projects'] = [
      '#type' => 'select',
      '#title' => $this->t('Project'),
      '#chosen' => TRUE,
      '#required' => TRUE,
      '#options' => $projects_list,
      '#ajax' => [
        'callback' => '::updateFormCallback',
        'wrapper' => 'media-piksel-library',
        'url' => Url::fromRoute('media_library.ui'),
        'options' => [
          'query' => $this->getMediaLibraryState($form_state)->all() + [
            FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
          ],
        ],
      ],
    ];

    $programs_array = [];

    if (!empty($form_state->getValue('projects'))) {
      $programs_list = [];
      $project_id = $options[$form_state->getValue('projects')]['uuid'];
      $optionsPrograms = $this->pikselSearch->searchPrograms($provider_name, $project_id);
      foreach ($optionsPrograms as $key => $program) {
        $programs_list[$program['uuid']] = $program['Title'];
        $programs_array[$program['uuid']] = $program;
      }
      $form['input_wrapper']['program'] = [
        '#type' => 'select',
        '#title' => $this->t('Program'),
        '#required' => TRUE,
        '#options' => $programs_list,
        '#ajax' => [
          'callback' => '::updateFormCallback',
          'wrapper' => 'media-piksel-library',
          'url' => Url::fromRoute('media_library.ui'),
          'options' => [
            'query' => $this->getMediaLibraryState($form_state)->all() + [
              FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
            ],
          ],
        ],
      ];
    }

    if (!empty($form_state->getValue('program'))) {

      $program_array = $programs_array[$form_state->getValue('program')];

      $form['input_wrapper']['details'] = [
        'container' => [
          '#type' => 'html_tag',
          '#attributes' => [
            'class' => ['container'],
          ],
          '#tag' => 'div',
          'description'  => [
            '#type'   => 'html_tag',
            '#tag'    => 'p',
            '#value'  => t('Description: ') . $program_array['Description'],
          ],
          'duration'  => [
            '#type'   => 'html_tag',
            '#tag'    => 'p',
            '#value'  => t('Duration: ') . $program_array['duration'],
          ],
          'image'  => [
            '#type'   => 'html_tag',
            '#tag'    => 'p',
            '#value'  => '<img width="320" src="' . $program_array['asset']['thumbnailUrl'] . '"/>',
          ],
        ],
      ];

      $form['input_wrapper']['actions'] = [
        '#type' => 'actions',
        'import' => [
          '#type' => 'submit',
          '#value' => $this->t('Import Piksel Program'),
          '#button_type' => 'primary',
          '#validate' => ['::importButtonValidate'],
          '#submit' => ['::importButtonSubmit'],
          '#ajax' => [
            'callback' => '::updateFormCallback',
            'wrapper' => 'media-library-wrapper',
            'url' => Url::fromRoute('media_library.ui'),
            'options' => [
              'query' => $this->getMediaLibraryState($form_state)->all() + [
                FormBuilderInterface::AJAX_FORM_REQUEST => TRUE,
              ],
            ],
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * Validates the form for the import button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function importButtonValidate(array $form, FormStateInterface $form_state): void {
    if (empty($form_state->getValue('program'))) {
      $form_state->setErrorByName('program', $this->t('No items selected.'));
    }
  }

  /**
   * Submit handler for the import button.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function importButtonSubmit(array $form, FormStateInterface $form_state): void {
    $this->processInputValues([$form_state->getValue('program')], $form, $form_state);
  }

}
