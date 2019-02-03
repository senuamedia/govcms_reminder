<?php

namespace Drupal\govcms_reminder\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'reminder' entity field type.
 *
 * @FieldType(
 *   id = "reminder",
 *   label = @Translation("Reminder"),
 *   description = @Translation("An entity field containing a date to send reminder email."),
 *   no_ui = TRUE,
 *   default_widget = "reminder",
 *   list_class = "\Drupal\govcms_reminder\Plugin\Field\FieldType\ReminderFieldItemList",
 * )
 */
class ReminderItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['rtid'] = DataDefinition::create('integer')
      ->setLabel(t('Reminder type id'));
    $properties['rid'] = DataDefinition::create('integer')
      ->setLabel(t('Reminder id'));
    $properties['date_time'] = DataDefinition::create('any')
      ->setLabel(t('Reminder time'));
    $properties['recurring'] = DataDefinition::create('string')
      ->setLabel(t('Recurring Type'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return ($this->rid === NULL || $this->rid === '')
      && ($this->rtid === NULL || $this->rtid === '')
      && ($this->date_time === NULL || $this->date_time === '')
      && ($this->recurring === NULL || $this->recurring === '');
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    // Do some actions before saving.
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $entity = $this->getEntity();

    $fields = [
      'rtid' => $this->rtid,
      'entity_label' => $entity->label(),
      'entity_id' => $entity->id(),
      'date_time' => $this->date_time->getTimestamp(),
      'recurring' => $this->recurring,
      'status' => 0,
    ];

    try {
      if ($this->rid === NULL || $this->rid === '') {
        $reminder = \Drupal::service('govcms_reminder.reminder_storage')->save($fields);
        \Drupal::service('govcms_reminder.reminder_manager')->updateLogger($reminder);
      }
      else {
        $current_reminder = \Drupal::service('govcms_reminder.reminder_storage')->load($this->rid);
        if ($current_reminder) {
          if ($current_reminder['date_time'] != $reminder['date_time']
            && $current_reminder['recurring'] != $reminder['recurring']) {
            $reminder = \Drupal::service('govcms_reminder.reminder_storage')->save($fields, $this->rid);
            \Drupal::service('govcms_reminder.reminder_manager')->updateLogger($reminder);
          }
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addWarning("Can't save reminder, please view the log to know the detail.");
    }

  }

}
