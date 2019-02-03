<?php

namespace Drupal\govcms_reminder\Plugin\Field\FieldType;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\govcms_reminder\ReminderType;

/**
 * Represents a configurable entity path field.
 */
class ReminderFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    $empty = TRUE;
    if (!$entity->isNew()) {
      $reminder = \Drupal::service('govcms_reminder.reminder_storage')->load(['entity_id' => $entity->id()]);
      if ($reminder) {
        $empty = FALSE;
        $this->list[0] = $this->createItem(0, $reminder);
      }
    }

    if ($empty) {
      // Get the reminder type if new entity.
      $reminder_types = ReminderType::loadMultiple(
        [],
        [
          'entity_type' => $entity->getEntityTypeId(),
          'bundle' => $entity->bundle(),
        ]
      );

      if ($reminder_types) {
        $reminder_type = reset($reminder_types);
        // @todo: user can select reminder type for the entity.
        $this->list[0] = $this->createItem(0, ['rtid' => $reminder_type->id()]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'view') {
      return AccessResult::allowed();
    }
    return AccessResult::allowedIfHasPermissions($account, ['create govcms reminder', 'administer govcms reminder'], 'OR')->cachePerPermissions();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete all aliases associated with this entity.
    $entity = $this->getEntity();

    $reminder = \Drupal::service('govcms_reminder.reminder_storage')->load(['entity_id' => $entity->id()]);
    if ($reminder) {
      $deleted = \Drupal::service('govcms_reminder.reminder_storage')->delete(['rid' => $reminder['rid']]);
      \Drupal::service('govcms_reminder.reminder_manager')->updateLogger($reminder, FALSE);
    }
  }

}
