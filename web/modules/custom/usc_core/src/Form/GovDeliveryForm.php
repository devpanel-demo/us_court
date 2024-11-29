<?php

namespace Drupal\usc_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class GovDeliveryForm.
 */
class GovDeliveryForm extends FormBase {

  const GOV_DELIVERY_URL = 'https://public.govdelivery.com/accounts/USFEDCOURTS/subscribers/qualify';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gov_delivery_form';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \InvalidArgumentException
   */
  public function buildForm(array $form, FormStateInterface $form_state, string $topic_id = '') {

    $options = [
      'query' => [
        'topic_id' => $topic_id,
      ],
      'absolute' => TRUE,
    ];

    $url = Url::fromUri($this::GOV_DELIVERY_URL, $options);

    $form['#action'] = $url->toString();

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];

    $form['topic_id'] = [
      '#type' => 'hidden',
      '#value' => $topic_id,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No need to process the form submission here as it will be submitted to the external URL.
  }

}
