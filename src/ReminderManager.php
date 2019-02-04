<?php

namespace Drupal\govcms_reminder;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Markup;

/**
 * Manager class.
 */
class ReminderManager {

  /**
   * The reminder storage.
   *
   * @var \Drupal\govcms_reminder\ReminderStorage
   */
  protected $storage;

  /**
   * The logger storage.
   *
   * @var \Drupal\govcms_reminder\LoggerStorage
   */
  protected $loggerStorage;

  /**
   * Constructs a ReminderManager.
   *
   * @param \Drupal\govcms_reminder\ReminderStorage $storage
   *   The reminder storage service.
   * @param \Drupal\govcms_reminder\LoggerStorage $loggerStorage
   *   The logger storage service.
   */
  public function __construct(ReminderStorage $storage, LoggerStorage $loggerStorage) {
    $this->storage = $storage;
    $this->loggerStorage = $loggerStorage;
  }

  /**
   * {@inheritdoc}
   */
  public function updateLogger($reminder, $create_new = TRUE) {
    // Find logger status = 0.
    $loggers = $this->loggerStorage->loadMultiple(['rid' => $reminder['rid'], 'status' => 0]);
    if ($loggers) {
      foreach ($loggers as $key => $logger) {
        $this->loggerStorage->delete(['rlid' => $logger->rlid]);
      }
    }

    if ($create_new) {
      $reminder_type = ReminderType::load($reminder['rtid']);
      $mail_content = $reminder_type->getMailContent();
      // Write log for reminder.
      $logger_fields = [
        'rid' => $reminder['rid'],
        'mailto' => $reminder_type->getMailToRaw(),
        'subject' => $reminder_type->getSubject(),
        'body' => $mail_content['value'],
        'mailfrom' => \Drupal::config('system.site')->get('mail'),
        'status' => 0,
        'date_sent' => $reminder['date_time'],
      ];

      $logger = $this->loggerStorage->save($logger_fields);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendMails($time = '+1 day') {
    $count = 0;
    $connection = \Drupal::database();

    $select = $connection->select('govcms_reminder_logger', 'l');
    $select->join('govcms_reminders', 'r', 'r.rid = l.rid');

    $date_time = new DrupalDateTime($time);
    $results = $select->condition('l.status', 0)
      ->condition('date_sent', $date_time->getTimeStamp(), '<')
      ->fields('l')
      ->fields('r')
      ->execute()->fetchAll();

    $mailManager = \Drupal::service('plugin.manager.mail');

    foreach ($results as $data) {
      $mail_to = \unserialize($data->mailto);
      $mail_addresses = $this->getEmailAddresses($mail_to);

      if (!empty($mail_addresses)) {
        $params = [
          'subject' => $data->subject,
          'message' => Markup::create($data->body),
        ];

        $result = $mailManager->mail(
          'govcms_reminder',
          'reminder_send',
          implode(', ', $mail_addresses),
          [],
          $params,
          NULL
        );

        // Update status after sending the emails.
        $this->loggerStorage->save(['status' => 1], $data->rlid);
        $count++;

        // Need create new log if this is recurring reminder.
        if (!empty($data->recurring)) {
          $new_date = strtotime($data->recurring, $data->date_sent);

          $logger_fields = [
            'rid' => $data->rid,
            'mailto' => $data->mailto,
            'subject' => $data->subject,
            'body' => $data->body,
            'mailfrom' => $data->mailfrom,
            'status' => 0,
            'date_sent' => $new_date,
          ];

          $logger = $this->loggerStorage->save($logger_fields);
        }

      }
    }

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmailAddresses($mail_to) {
    $emails = [];

    if (isset($mail_to['email_addresses'])) {
      $emails += $mail_to['email_addresses'];
    }
    if (isset($mail_to['users'])) {
      $emails += $this->getEmailsFromUsers($mail_to['users']);
    }
    if (isset($mail_to['roles'])) {
      $emails += $this->getEmailsFromRoles($mail_to['roles']);
    }

    return $emails;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmailsFromUsers($users) {
    $connection = \Drupal::database();

    try {
      $select = $connection->select('users_field_data', 'f');
      $select->condition('f.uid', $users, 'IN');
      return $select->fields('f', ['mail'])->execute()->fetchCol();
    }
    catch (\Exception $e) {
      \Drupal::logger('govcms_reminder')->error($e->getMessage());
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEmailsFromRoles($roles) {

    $query = \Drupal::entityQuery('user')
      ->condition('status', 1);

    // All users are authenticated. We don't need to add condition.
    if (!isset($roles['authenticated'])) {
      $query->condition('roles', $roles, 'IN');
    }
    $ids = $query->execute();

    return $this->getEmailsFromUsers($ids);
  }

}
