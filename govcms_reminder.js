/**
 * @file
 * Attaches behaviors for the GovCMS Reminder module.
 */

(function ($, Drupal) {

  Drupal.govCMSReminder = Drupal.govCSMReminder || {
    '$reminderForm': $(".reminder-form"),
    '$recurringType': $(".reminder-form").find('#recurring-type-wrapper'),
    '$dateTime': $(".reminder-form").find('#date-time-wrapper'),
  };

  Drupal.behaviors.govCMSReminder = {
    attach: function attach(context) {

      Drupal.govCMSReminder.dateTimeAction();
      Drupal.govCMSReminder.$recurringType
        .find('input.form-radio')
        .off('change.reminder_form')
        .on('change.reminder_form', function(event) {
          Drupal.govCMSReminder.dateTimeAction();
        });
    }
  };

  Drupal.govCMSReminder.dateTimeAction = function() {
    var $recurringType = Drupal.govCMSReminder.$recurringType;
    var $dateTime = Drupal.govCMSReminder.$dateTime;

    if ($recurringType.find('input.form-radio:checked').val() == 'one_time') {
      $dateTime.css('display', 'block');
    }
    else {
      $dateTime.css('display', 'none');
    }
  };

})(jQuery, Drupal);
