<?php

/**
 * @file
 * Drush custom commands.
 */

/**
 * Implements hook_drush_command().
 */
function govcms_reminder_drush_command() {
  $commands['reminders-send'] = [
    'description' => 'Send reminder emails.',
    'options' => [
      'time' => 'Time range',
    ],
    'aliases' => ['reminders:send'],
    'callback' => 'govcms_reminder_send_command',
  ];

  return $commands;
}

/**
 * Send reminder email callback.
 */
function govcms_reminder_send_command() {
  $time = drush_get_option('time', FALSE);
  drush_print('Start to send reminder emails');
  if ($time) {
    $count = \Drupal::service('govcms_reminder.reminder_manager')->sendMails($time);
  }
  else {
    $count = \Drupal::service('govcms_reminder.reminder_manager')->sendMails();
  }
  drush_log('Finished! Sent ' . $count . ' reminders.', 'success');
}
