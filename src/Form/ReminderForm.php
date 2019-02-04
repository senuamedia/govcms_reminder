<?php

namespace Drupal\govcms_reminder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\govcms_reminder\ReminderStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\govcms_reminder\ReminderType;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\govcms_reminder\ReminderManager;

/**
 * Class ReminderForm.
 */
class ReminderForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reminder_form';
  }

  /**
   * The reminder id.
   */
  protected $rid;

  /**
   * The reminder storage.
   *
   * @var \Drupal\govcms_reminder\ReminderStorage
   */
  protected $reminderStorage;

  /**
   * The reminder manager.
   *
   * @var \Drupal\govcms_reminder\ReminderManager
   */
  protected $manager;

  /**
   * Constructs a new ReminderController.
   *
   * @param \Drupal\govcms_reminder\ReminderStorage $reminder_storage
   * @param \Drupal\govcms_reminder\ReminderManager $manager
   *   The path alias storage.
   */
  public function __construct(ReminderStorage $reminder_storage, ReminderManager $manager) {
    $this->reminderStorage = $reminder_storage;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('govcms_reminder.reminder_storage'),
      $container->get('govcms_reminder.reminder_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $rid = NULL) {
    $this->rid = $rid;
    $reminder = $this->reminderStorage->load(['rid' => $this->rid]);
    $form['rid'] = [
      '#type' => 'item',
      '#title' => t('RID'),
      '#markup' => $rid,
    ];

    // kint($reminder); exit;

    $form['entity_id'] = [
      '#type' => 'item',
      '#title' => t('ENTITY ID'),
      '#markup' => $reminder['entity_id'],
    ];

    $form['title'] = [
      '#type' => 'item',
      '#title' => t('TITLE'),
      '#markup' => $reminder['entity_label'],
    ];

    $form['date_time'] = [
      '#type' => 'datetime',
      '#title' => t('Date Time'),
      '#required' => $form['#required'],
      '#description' => $this->t('The date you want to send the reminder.'),
      '#date_date_format' => 'Y-m-d H:i:s',
      '#default_value' => is_numeric($reminder['date_time']) ? DrupalDateTime::createFromTimestamp($reminder['date_time']) : NULL,
      '#prefix' => '<div id="date-time-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['recurring_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => t('Recurring'),
      '#default_value' => !empty($reminder['recurring']) ? 1 : 0,
    ];

    $form['recurring'] = [
      '#type' => 'textfield',
      '#title' => t('How often (time period)'),
        '#description' => $this->t('The time period can be +1 hour, +1 day, etc and indicates the period between each reminder being sent.'),
      '#default_value' => !empty($reminder['recurring']) ? $reminder['recurring'] : NULL,
      '#states' => [
        'visible' => [
          ':input#edit-recurring-checkbox' => ['checked' => TRUE],
        ],
        'required' => [
          ':input#edit-recurring-checkbox' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#limit_validation_errors' => [],
      '#submit' => ['::cancelCallback'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->checkValuesUpdate($form, $form_state)) {
      $values = $form_state->getValues();
      $reminder_values = is_array($values) ? $values : NULL;

      if (!empty($reminder_values['recurring_checkbox'])) {
        if (empty($reminder_values['recurring'])) {
          $form_state->setError($form['recurring'], t('Please fill the time period.'));
        }

        if (empty($reminder_values['date_time'])) {
          $date_time = new DrupalDateTime();
          $form_state->setValue('date_time', $date_time);
        }
      }

      if (!empty($reminder_values['date_time'])) {
        $date_time_obj = $reminder_values['date_time'];
        if ($date_time_obj instanceof DrupalDateTime) {
          $date_time = $date_time_obj->getTimeStamp();
          if ($form['date_time']['#default_value'] != $reminder_values['date_time']) {
            $now = strtotime('now');
            $more10years = strtotime('+5 year');
            if ($date_time < $now || $date_time > $more10years) {
              $form_state->setError($form['date_time'], t('The date is out of range.'));
            }
          }
        }
        else {
          $form_state->setError($form['date_time'], t("The date isn't correct."));
        }
      }
      else {
        $date_time = new DrupalDateTime();
        $form_state->setValue('date_time', $date_time);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->checkValuesUpdate($form, $form_state)) {
      $values = $form_state->getValues();
      $date_time = $values['date_time'];
      $recurring_checkbox = $values['recurring_checkbox'];
      $recurring = (isset($values['recurring']) && $recurring_checkbox == 1) ? $values['recurring'] : '';
      $fields = [
        'date_time' => $date_time->getTimestamp(),
        'recurring' => $recurring,
      ];

      $reminder = $this->reminderStorage->save($fields, $this->rid);
      $reminder = $this->reminderStorage->load($this->rid);
      $this->manager->updateLogger($reminder, FALSE);
    }
    $form_state->setRedirect('govcms_reminder.admin_overview');
  }

  /**
   * Callback function for cancel.
   */
  public function cancelCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('govcms_reminder.admin_overview');
  }

  public function checkValuesUpdate(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $date_time_default_value = $form['date_time']['#default_value'];
    $date_time_default_value = ($date_time_default_value instanceof DrupalDateTime) ? $date_time_default_value : 'false2';
    $recurring_checkbox_default_value = $form['recurring_checkbox']['#default_value'];
    $recurring_default_value = isset($form['recurring']['#default_value']) ? $form['recurring']['#default_value'] : '';

    $date_time = $values['date_time'];
    $date_time = ($date_time_default_value instanceof DrupalDateTime) ? $date_time : 'false1';
    $recurring_checkbox = $values['recurring_checkbox'];
    $recurring = isset($values['recurring']) ? $values['recurring'] : '';

    if ($date_time != $date_time_default_value
      || $recurring_checkbox != $recurring_checkbox_default_value
      || $recurring != $recurring_default_value) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
