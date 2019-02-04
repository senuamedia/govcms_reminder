<?php

namespace Drupal\govcms_reminder\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'reminder' widget.
 *
 * @FieldWidget(
 *   id = "reminder",
 *   label = @Translation("Reminder"),
 *   field_types = {
 *     "reminder"
 *   }
 * )
 */
class ReminderWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();

    $element += [
      '#element_validate' => [[get_class($this), 'validateFormElement']],
      '#attributes' => [
        'class' => ['reminder-form'],
      ],
    ];

    $element['date_time'] = [
      '#type' => 'datetime',
      '#title' => t('Date Time'),
      '#required' => $element['#required'],
      '#description' => $this->t('The date you want to send the reminder.'),
      '#date_date_format' => 'Y-m-d H:i:s',
      '#default_value' => is_numeric($items[$delta]->date_time) ? DrupalDateTime::createFromTimestamp($items[$delta]->date_time) : NULL,
      '#prefix' => '<div id="date-time-wrapper">',
      '#suffix' => '</div>',
    ];

    $element['recurring_checkbox'] = [
      '#type' => 'checkbox',
      '#title' => t('Recurring'),
      '#default_value' => !empty($items[$delta]->recurring) ? TRUE : FALSE,
    ];

    $help_link = Link::fromTextAndUrl(
      'strtotime',
      Url::fromUri('https://secure.php.net/manual/en/function.strtotime.php')
    );
    $element['recurring'] = [
      '#type' => 'textfield',
      '#title' => t('How often (time period)'),
      '#default_value' => !empty($items[$delta]->recurring) ? $items[$delta]->recurring : NULL,
      '#description' => $this->t('The time period can be +1 hour, +1 day, etc and indicates the period between each reminder being sent. See the @link php function.', [
        '@link' => $help_link->toString(),
      ]),
      '#states' => [
        'visible' => [
          ':input[name="reminder[0][recurring_checkbox]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="reminder[0][recurring_checkbox]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $element['rid'] = [
      '#type' => 'value',
      '#value' => $items[$delta]->rid,
    ];

    $element['rtid'] = [
      '#type' => 'value',
      '#value' => $items[$delta]->rtid,
    ];

    // If the advanced settings tabs-set is available (normally rendered in the
    // second column on wide-resolutions), place the field as a details element
    // in this tab-set.
    if (isset($form['advanced'])) {
      $element += [
        '#type' => 'details',
        '#title' => t('Reminder settings'),
        '#open' => !empty($items[$delta]->date_time),
        '#group' => 'advanced',
        '#access' => TRUE,
      ];
      $element['#weight'] = 99;
    }

    return $element;
  }

  /**
   * Form element validation handler for URL alias form element.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateFormElement(array &$element, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $reminder_values = is_array($values['reminder']) ? reset($values['reminder']) : NULL;

    if (!empty($reminder_values['recurring_checkbox'])) {
      if (empty($reminder_values['recurring'])) {
        $form_state->setError($element['recurring'], t('Please fill the time period.'));
      }

      if (empty($reminder_values['date_time'])) {
        $date_time = new DrupalDateTime();
        $form_state->setValue(['reminder', 0, 'date_time'], $date_time);
      }
    }

    if (!empty($reminder_values['date_time'])) {
      $date_time_obj = $reminder_values['date_time'];
      if ($date_time_obj instanceof DrupalDateTime) {
        $date_time = $date_time_obj->getTimeStamp();
        if ($element['date_time']['#default_value'] != $reminder_values['date_time']) {
          $now = strtotime('now');
          $more10years = strtotime('+5 year');
          if ($date_time < $now || $date_time > $more10years) {
            $form_state->setError($element['date_time'], t('The date is out of range.'));
          }
        }
      }
      else {
        $form_state->setError($element['date_time'], t("The date isn't correct."));
      }
    }
    else {
      $date_time = new DrupalDateTime();
      $form_state->setValue(['reminder', 0, 'date_time'], $date_time);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return $element['date_time'];
  }

}
