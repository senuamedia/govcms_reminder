<?php

namespace Drupal\govcms_reminder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ReminderFilterForm.
 */
class ReminderFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reminder_admin_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $keys = NULL) {
    $form['#attributes'] = ['class' => ['search-form']];
    $form['basic'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter entity label'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['basic']['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Entity label'),
      '#title_display' => 'invisible',
      '#default_value' => $keys,
      '#maxlength' => 128,
      '#size' => 25,
    ];
    $form['basic']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    if ($keys) {
      $form['basic']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#submit' => ['::resetForm'],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('govcms_reminder.admin_overview', [], [
      'query' => ['search' => trim($form_state->getValue('filter'))],
    ]);
  }

  /**
   * Callback function for cancel.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('govcms_reminder.admin_overview');
  }

}
