<?php

namespace Drupal\govcms_reminder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\govcms_reminder\ReminderStorage;
use Drupal\govcms_reminder\ReminderType;

/**
 * Returns responses for govcms_reminder module routes.
 */
class ReminderController extends ControllerBase {

  /**
   * The reminder storage.
   *
   * @var \Drupal\govcms_reminder\ReminderStorage
   */
  protected $reminderStorage;

  /**
   * Constructs a new ReminderController.
   *
   * @param \Drupal\govcms_reminder\ReminderStorage $reminder_storage
   *   The path alias storage.
   */
  public function __construct(ReminderStorage $reminder_storage) {
    $this->reminderStorage = $reminder_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('govcms_reminder.reminder_storage')
    );
  }

  /**
   * Show the reminder settings.
   */
  public function adminOverview(Request $request) {
    $keys = $request->query->get('search');
    // Add the filter form above the overview table.
    $build['reminder_admin_filter_form'] = $this->formBuilder()->getForm('Drupal\govcms_reminder\Form\ReminderFilterForm', $keys);

    $headers = [
      $this->t('RID'),
      $this->t('Entity ID'),
      $this->t('Title'),
      $this->t('Entity type'),
      $this->t('Bundle'),
      $this->t('Date Time'),
      $this->t('Recurring'),
      $this->t('Operations'),
    ];

    $rows = [];
    foreach ($this->reminderStorage->getRemindersForAdminListing($keys) as $data) {
      $row['rid'] = [
        'data' => $data->rid,
        'class' => 'table-filter-text-source',
      ];

      $reminder_type = ReminderType::load($data->rtid);

      $entity = \Drupal::entityTypeManager()
        ->getStorage($reminder_type->getEntityType())
        ->load($data->entity_id);
      $row['entity_id'] = [
        'data' => $entity->id(),
        'class' => 'table-filter-text-source',
      ];

      $row['title'] = [
        'data' => Link::fromTextAndUrl(
          $entity->label(),
          $entity->toUrl()
        ),
        'class' => 'table-filter-text-source',
      ];

      $row['entity_type'] = [
        'data' => $this->getEntityTypeLabel($entity->getEntityType()),
        'class' => 'table-filter-text-source',
      ];

      $row['bundle'] = [
        'data' => $this->getBundleLabel($entity->getEntityTypeId(), $entity->bundle()),
        'class' => 'table-filter-text-source',
      ];

      $date_time = DrupalDateTime::createFromTimestamp($data->date_time);
      $row['date_time'] = [
        'data' => $date_time->format('d/m/Y H:i:s'),
        'class' => 'table-filter-text-source',
      ];

      $row['recurring'] = [
        'data' => $data->recurring,
        'class' => 'table-filter-text-source',
      ];

      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => [
          'edit' => [
            'title' => $this->t('Edit'),
            'url' => Url::fromRoute('govcms_reminder.reminder_edit', ['rid' => $data->rid]),
          ],
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('govcms_reminder.reminder_delete', ['rid' => $data->rid]),
          ],
        ],
      ];

      $rows[$data->rid] = $row;
    }

    $build['reminders'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No reminder found.'),
      '#sticky' => TRUE,
    ];
    $build['path_pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Get entity label.
   */
  public function getEntityTypeLabel($entity_type_defination) {
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

}
