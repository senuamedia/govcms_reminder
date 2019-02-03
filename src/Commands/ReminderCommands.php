<?php

namespace Drupal\govcms_reminder\Commands;

use Drush\Commands\DrushCommands;

/**
 * Reminder drush commends.
 */
class ReminderCommands extends DrushCommands {

  /**
   * Sends all reminders.
   *
   * @command reminder:send
   * @aliases re-send
   * @options rid Reminder ID to run.
   */
  public function send($options = ['rid' => NULL]) {
    if ($options['rid']) {
      $this->output()->writeln('Hello! This is the drush send command with rid: ' . $options['rid'] . '.');
    }
    else {
      $this->output()->writeln('Hello! This is the drush send command.');
    }
  }

}
