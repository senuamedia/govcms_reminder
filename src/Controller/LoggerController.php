<?php

namespace Drupal\govcms_reminder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\govcms_reminder\LoggerStorage;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\govcms_reminder\Controller\ReminderTypeController;
use Drupal\Core\Database\Connection;

/**
 * Returns responses for govcms_reminder module routes.
 */
class LoggerController extends ControllerBase {

  /**
   * The logger storage.
   *
   * @var \Drupal\govcms_reminder\LoggerStorage
   */
  protected $storage;

  /**
   * Construction.
   *
   * @param \Drupal\govcms_reminder\LoggerStorage $storage
   *   The logger storage service.
   */
  public function __construct(LoggerStorage $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('govcms_reminder.logger_storage')
    );
  }

  /**
   * Show reminder settings.
   */
  public function adminOverview() {
    $build = ['#markup' => $this->t('LoggerController')];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function sentOverview() {
    $conditions = ['l.status' => 1];
    return $this->renderLogDataTable($conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function remainingOverview() {
    $conditions = ['l.status' => 0];
    return $this->renderLogDataTable($conditions);
  }

  /**
   * {@inheritdoc}
   */
  public function renderLogDataTable($conditions) {
    $header = [
      $this->t('RLID'),
      $this->t('RID'),
      $this->t('Entity label'),
      $this->t('Subject'),
      $this->t('Date sent'),
      $this->t('Status'),
      $this->t('Operations'),
    ];

    $logs = $this->storage->getLogsForAdminListing($conditions);
    // kint($logs); exit;
    $rows = [];
    foreach ($logs as $data) {
      $row['rlid'] = [
        'data' => $data->rlid,
        'class' => 'table-filter-text-source',
      ];

      $row['rid'] = [
        'data' => $data->rid,
        'class' => 'table-filter-text-source',
      ];

      $row['entity_label'] = [
        'data' => $data->entity_label,
        'class' => 'table-filter-text-source',
      ];

      $row['subject'] = [
        'data' => $data->subject,
        'class' => 'table-filter-text-source',
      ];

      $date_sent = DrupalDateTime::createFromTimestamp($data->date_sent);
      $row['date_sent'] = [
        'data' => $date_sent->format('d/m/Y H:i:s'),
        'class' => 'table-filter-text-source',
      ];

      $row['status'] = [
        'data' => $data->status,
        'class' => 'table-filter-text-source',
      ];

      $row['operations']['data'] = [
        '#type' => 'operations',
        '#links' => [
          'view' => [
            'title' => $this->t('View'),
            'url' => Url::fromRoute('govcms_reminder.logger.show_log', ['rlid' => $data->rlid]),
          ],
        ],
      ];

      $rows[$data->rlid] = $row;
    }

    $output['sent_logs'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => 'No log is found',
      '#sticky' => TRUE,
    ];
    $output['path_pager'] = ['#type' => 'pager'];

    return $output;
  }

  /**
   * Get mail to value.
   */
  public function renderLogMailTo($mail_to) {
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

  public function showLogDetail($rlid) {
    $build = [];
    $select = db_select('govcms_reminder_logger', 'l');
    $select->leftjoin('govcms_reminders', 'r', 'r.rid = l.rid');
    $log = $select->fields('l')
                  ->fields('r', ['entity_label'])
                  ->condition('l.rlid', $rlid, '=')
                  ->execute()
                  ->fetchAll();

    if (isset($log[0])) {
      $mailto = unserialize($log[0]->mailto);
      $mailto_markub = $this->renderLogMailTo($mailto);
      $date_sent = DrupalDateTime::createFromTimestamp($log[0]->date_sent);

      // print_r($mailto_markub); exit;
      $rows = [
        [
          ['data' => $this->t('RLID'), 'header' => TRUE],
          $this->t($log[0]->rlid),
        ],

        [
          ['data' => $this->t('RID'), 'header' => TRUE],
          $this->t($log[0]->rid),
        ],

        [
          ['data' => $this->t('Entity label'), 'header' => TRUE],
          $this->t($log[0]->entity_label),
        ],

        [
          ['data' => $this->t('Mail to'), 'header' => TRUE],
          ['data' => $mailto_markub],
        ],

        [
          ['data' => $this->t('Subject'), 'header' => TRUE],
          $this->t($log[0]->subject),
        ],

        [
          ['data' => $this->t('Date sent'), 'header' => TRUE],
          ['data' => $date_sent->format('d/m/Y H:i:s')],
        ],

        [
          ['data' => $this->t('Status'), 'header' => TRUE],
          $this->t($log[0]->status),
        ],
      ];

      $build['log_table'] = [
        '#type' => 'table',
        '#rows' => $rows,
        '#attributes' => ['class' => ['dblog-event']],
        '#attached' => [
          'library' => ['dblog/drupal.dblog'],
        ],
      ];
    }

    return $build;
  }
}
