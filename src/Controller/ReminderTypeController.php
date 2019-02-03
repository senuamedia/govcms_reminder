<?php

namespace Drupal\govcms_reminder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\govcms_reminder\ReminderType;

/**
 * Returns responses for govcms_reminder module routes.
 */
class ReminderTypeController extends ControllerBase {

  /**
   * Show the reminder settings.
   */
  public function typeList() {
    $headers = [
      $this->t('ID'),
      $this->t('Entity type'),
      $this->t('Bundle'),
      $this->t('Subject'),
      $this->t('Send reminder to'),
      $this->t('Created'),
      $this->t('Changed'),
      $this->t('Operations'),
    ];

    $rows = [];
    $reminder_types = ReminderType::loadMultiple();
    foreach ($reminder_types as $reminder_type) {
      $row['id'] = [
        'data' => $reminder_type->id(),
        'class' => 'table-filter-text-source',
      ];
      $row['entity_type'] = [
        'data' => $this->getEntityTypeLabel($reminder_type->getEntityType()),
        'class' => 'table-filter-text-source',
      ];
      $row['bundle'] = [
        'data' => $this->getBundleLabel($reminder_type->getEntityType(), $reminder_type->getBundle()),
        'class' => 'table-filter-text-source',
      ];
      $row['subject'] = [
        'data' => $reminder_type->getSubject(),
        'class' => 'table-filter-text-source',
      ];
      $row['mail_to'] = [
        'data' => $this->renderMailTo($reminder_type->getDecodedMailTo()),
        'class' => 'table-filter-text-source',
      ];
      $row['created'] = [
        'data' => date('d/m/Y', $reminder_type->getCreated()),
        'class' => 'table-filter-text-source',
      ];
      $row['changed'] = [
        'data' => date('d/m/Y', $reminder_type->getChanged()),
        'class' => 'table-filter-text-source',
      ];
      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('govcms_reminder.types_edit', ['rtid' => $reminder_type->id()]),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('govcms_reminder.types_delete', ['rtid' => $reminder_type->id()]),
          ],
        ],
      ];

      $rows[$reminder_type->id()] = $row;
    }

    ksort($rows);
    $output['reminders'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No reminder found.'),
      '#sticky' => TRUE,
    ];

    return $output;
  }

  /**
   * Get entity label.
   */
  public function getEntityTypeLabel($entity_type) {
    $entity_type_definations = \Drupal::entityTypeManager()->getDefinitions();

    $entity_type_defination = $entity_type_definations[$entity_type];
    $label = $entity_type_defination->getLabel();
    if ($label instanceof TranslatableMarkup) {
      $entity_types[$entity_type] = $label->render();
    }
    else {
      $entity_types[$entity_type] = $label;
    }

    return $label;
  }

  /**
   * Get Bundle name.
   */
  public function getBundleLabel($entity_type, $bundle) {
    $bundle_info = \Drupal::entityManager()->getBundleInfo($entity_type);
    if (isset($bundle_info[$bundle])) {
      $bundle_label = $bundle_info[$bundle]['label'];
      if ($bundle_label instanceof TranslatableMarkup) {
        return $bundle_label->render();
      }
      else {
        return $bundle_label;
      }
    }

    return NULL;
  }

  /**
   * Get mail to value.
   */
  public function renderMailTo($mail_to) {
    if (is_string($mail_to)) {
      $mail_to = unserialize($mail_to);
    }

    $output = [];
    if (!empty($mail_to['roles'])) {
      $output[] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t('Roles'),
        '#items' => $mail_to['roles'],
      ];
    }

    if (!empty($mail_to['email_addresses'])) {
      $output[] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t('Email addresses'),
        '#items' => $mail_to['email_addresses'],
      ];
    }

    if (!empty($mail_to['users'])) {
      $users = user_load_multiple($mail_to['users']);
      $user_items = [];
      foreach ($users as $user) {
        $user_items[] = $user->getUsername();
      }
      $output[] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#title' => $this->t('Users'),
        '#items' => $user_items,
      ];
    }

    return $output;
  }

}
