<?php

namespace Drupal\govcms_reminder\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\govcms_reminder\ReminderType;

/**
 * Defines a confirmation form to confirm deletion of something by id.
 */
class ReminderTypeDeleteForm extends ConfirmFormBase {

  /**
   * ID of the item to delete.
   *
   * @var Drupal\govcms_reminder\ReminderType
   */
  protected $reminderType;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "reminder_type_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $rtid = NULL) {
    $this->reminderType = ReminderType::load($rtid);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->reminderType->delete();
    $messenger = \Drupal::messenger();
    $messenger->addMessage('Reminder type has been removed.');
    $form_state->setRedirect('govcms_reminder.types');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('govcms_reminder.types');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete the reminder type %id?', ['%id' => $this->reminderType->id()]);
  }

}
