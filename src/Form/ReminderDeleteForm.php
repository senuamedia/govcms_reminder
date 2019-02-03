<?php

namespace Drupal\govcms_reminder\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\govcms_reminder\ReminderStorage;
use Drupal\govcms_reminder\ReminderManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form to confirm deletion of reminder and log not yet sent by id.
 */
class ReminderDeleteForm extends ConfirmFormBase {

  /**
   * The reminder id.
   */
  protected $rid;

  /**
   * The reminder storage.
   *
   * @var \Drupal\govcms_reminder\ReminderStorage
   */
  protected $storage;

  /**
   * The reminder manager.
   *
   * @var \Drupal\govcms_reminder\ReminderManager
   */
  protected $manager;

  /**
   * Construction.
   *
   * @param \Drupal\govcms_reminder\ReminderStorage $storage
   * @param \Drupal\govcms_reminder\ReminderManager $manager
   *   The logger storage service.
   */
  public function __construct(ReminderStorage $storage, ReminderManager $manager) {
    $this->storage = $storage;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('govcms_reminder.reminder_storage'),
      $container->get('govcms_reminder.reminder_manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return "reminder_delete_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $rid = NULL) {
    $this->rid = $rid;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $reminder = $this->storage->load($this->rid);
    $this->manager->updateLogger($reminder, FALSE);

    $this->storage->delete(['rid' => $this->rid]);
    $messenger = \Drupal::messenger();
    $messenger->addMessage('Reminder and all associated logs has been removed.');
    $form_state->setRedirect('govcms_reminder.admin_overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('govcms_reminder.admin_overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Do you want to delete the reminder %rid and all logs that have not been sent?', ['%rid' => $this->rid]);
  }

}
