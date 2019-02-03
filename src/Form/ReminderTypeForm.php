<?php

namespace Drupal\govcms_reminder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\Entity\Role;
use Drupal\govcms_reminder\ReminderType;

/**
 * Class ReminderForm.
 *
 * @package Drupal\govcms_reminder\Form
 */
class ReminderTypeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reminder_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $rtid = NULL) {
    $values = $form_state->getValues();
    $ajax_wrapper = 'reminder-form-ajax-wrapper';
    $field_config = FieldConfig::loadByName('node', 'content_1', 'field_field_text');

    if ($rtid) {
      $reminder_type = ReminderType::load($rtid);
      $form_state->setStorage(['reminder_type' => $reminder_type]);
    }

    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => $this->getEntityTypes(),
      '#default_value' => isset($reminder_type) ? $reminder_type->getEntityType() : NULL,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'entityTypeCallback'],
        'event' => 'change',
        'wrapper' => $ajax_wrapper,
      ],
    ];

    $bundles = [];
    if (!empty($values) && !empty($values['entity_type'])) {
      $bundles = $this->getBundles($values['entity_type']);
    }
    elseif (isset($reminder_type)) {
      $bundles = $this->getBundles($reminder_type->getEntityType());
    }
    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => $bundles,
      '#default_value' => isset($reminder_type) ? $reminder_type->getBundle() : NULL,
      '#required' => TRUE,
      '#prefix' => '<div id="' . $ajax_wrapper . '">',
      '#suffix' => '</div>',
    ];

    if ($rtid) {
      $form['entity_type']['#attributes']['disabled'] = 'disabled';
      $form['bundle']['#attributes']['disabled'] = 'disabled';
    }

    $form['mail_to'] = [
      '#type' => 'details',
      '#title' => $this->t('Send reminder to'),
      '#open' => TRUE,
    ];

    $form['mail_to']['roles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Roles'),
      '#default_value' => isset($reminder_type) && !empty($reminder_type->getMailTo('roles')) ? TRUE : FALSE,
    ];

    $form['mail_to']['roles_list'] = [
      '#type' => 'select',
      '#title' => $this->t('Select role(s):'),
      '#options' => $this->getSystemRoles(),
      '#multiple' => TRUE,
      '#default_value' => isset($reminder_type) ? $reminder_type->getMailTo('roles') : [],
      '#states' => [
        'visible' => [
          ':input[name="mail_to[roles]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="mail_to[roles]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['mail_to']['email_addresses'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Email addresses'),
      '#default_value' => isset($reminder_type) && !empty($reminder_type->getMailTo('email_addresses')) ? TRUE : FALSE,
    ];

    $form['mail_to']['email_addresses_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email addresses'),
      '#title_display' => 'none',
      '#description' => $this->t('Please enter one email address per line for multiple emails'),
      '#default_value' => isset($reminder_type) ? implode("\n", $reminder_type->getMailTo('email_addresses')) : [],
      '#states' => [
        'visible' => [
          ':input[name="mail_to[email_addresses]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="mail_to[email_addresses]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['mail_to']['users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Users'),
      '#default_value' => isset($reminder_type) && !empty($reminder_type->getMailTo('users')) ? TRUE : FALSE,
    ];

    $uids = isset($reminder_type) ? $reminder_type->getMailTo('users') : [];
    $form['mail_to']['users_list'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Users'),
      '#title_display' => 'none',
      '#description' => $this->t('Enter a comma separated list of user names.'),
      '#tags' => TRUE,
      '#target_type' => 'user',
      '#selection_handler' => 'views',
      '#selection_settings' => [
        'view' => [
          'view_name' => 'reminder_type_user_search',
          'display_name' => 'entity_reference_1',
          'arguments' => [],
        ],
        'match_operator' => 'CONTAINS',
      ],
      '#states' => [
        'visible' => [
          ':input[name="mail_to[users]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="mail_to[users]"]' => ['checked' => TRUE],
        ],
      ],
      '#default_value' => user_load_multiple($uids),
    ];

    $form['template'] = [
      '#type' => 'details',
      '#title' => $this->t('Template'),
      '#open' => TRUE,
    ];

    $form['template']['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#default_value' => isset($reminder_type) ? $reminder_type->getSubject() : NULL,
      '#required' => TRUE,
    ];

    $mail_content = isset($reminder_type) ? $reminder_type->getMailContent() : NULL;
    $form['template']['mail_content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email content'),
      '#default_value' => $mail_content ? $mail_content['value'] : NULL,
      '#format' => $mail_content ? $mail_content['format'] : 'full_html',
      '#rows' => 10,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#limit_validation_errors' => [],
      '#submit' => ['::cancelCallback'],
    ];

    $form['#tree'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();

    $request = \Drupal::request();
    if (!$request->isXmlHttpRequest()) {
      if (!isset($storage['reminder_type']) && !empty($values['entity_type']) && !empty($values['bundle'])) {
        // Check if entity type and bundle were added.
        $reminder_types = ReminderType::loadMultiple([], [
          'entity_type' => $values['entity_type'],
          'bundle' => $values['bundle'],
        ]);
        if (!empty($reminder_types)) {
          $form_state->setErrorByName('entity_type', $this->t('This reminder has been added already.'));
        }
      }

      if (empty($values['mail_to']['users_list'])
        && empty($values['mail_to']['roles_list'])
        && empty($values['mail_to']['email_addresses_list'])) {
        $form_state->setErrorByName('', $this->t('Please add the recipients the reminders will be sent to.'));
      }

      // Validate email addresses.
      if (!empty($values['mail_to']['email_addresses_list'])) {
        // In case only one email.
        $values['mail_to']['email_addresses_list'] .= "\n";
        $email_addresses = explode("\n", $values['mail_to']['email_addresses_list']);
        array_pop($email_addresses);

        $email_trimmed = [];
        foreach ($email_addresses as $email) {
          $email = trim($email);
          if (!\Drupal::service('email.validator')->isValid($email)) {
            $form_state->setErrorByName('mail_to][email_addresses_list', $this->t('Please fill correct email format.'));
            break;
          }
          $email_trimmed[] = $email;
        }
        $form_state->setValue(['mail_to', 'email_addresses_list'], $email_trimmed);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();

    if (isset($storage['reminder_type'])) {
      $reminder_type = $storage['reminder_type'];

      $reminder_type->setMailTo($this->mailToSerialize($values['mail_to']))
        ->setSubject($values['template']['subject'])
        ->setMailContent($values['template']['mail_content']);
      $reminder_type->save();
    }
    else {
      $reminder_type = new ReminderType();

      $mail_to = $this->mailToSerialize($values['mail_to']);
      $reminder_type->setEntityType($values['entity_type'])
        ->setBundle($values['bundle'])
        ->setMailTo($mail_to)
        ->setSubject($values['template']['subject'])
        ->setMailContent($values['template']['mail_content']);

      $reminder_type->save();
      drupal_flush_all_caches();
    }

    $messenger = \Drupal::messenger();
    $messenger->addMessage('Reminder type has been saved.');
    $form_state->setRedirect('govcms_reminder.types');
  }

  /**
   * Callback function for cancel.
   */
  public function cancelCallback(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('govcms_reminder.types');
  }

  /**
   * The callback function for when the `entity_type` element is changed.
   */
  public function entityTypeCallback(array $form, FormStateInterface $form_state) {
    // Return the element that will replace the wrapper (we return itself).
    return $form['bundle'];
  }

  /**
   * Get entity type list.
   */
  public function getEntityTypes() {
    $entity_types = [];
    $entity_type_definations = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($entity_type_definations as $entity_type => $definition) {
      $label = $definition->getLabel();
      if ($label instanceof TranslatableMarkup) {
        $entity_types[$entity_type] = $label->render();
      }
      else {
        $entity_types[$entity_type] = $label;
      }
    }

    return $entity_types;
  }

  /**
   * Get entity type list.
   */
  public function getBundles($entity_type) {
    $bundles = [];
    $bundle_info = \Drupal::entityManager()->getBundleInfo($entity_type);
    foreach ($bundle_info as $name => $info) {
      $bundles[$name] = $info['label'];
    }

    return $bundles;
  }

  /**
   * Get all system roles.
   */
  public function getSystemRoles() {
    $role_objects = Role::loadMultiple();
    $system_roles = array_combine(
      array_keys($role_objects),
      array_map(function ($a) {
        return $a->label();
      }, $role_objects)
    );
    return $system_roles;
  }

  /**
   * Handle mail to values.
   */
  public function mailToSerialize($mail_to) {
    $data = [];
    if ($mail_to['roles'] == 1) {
      $data['roles'] = $mail_to['roles_list'];
    }

    if ($mail_to['email_addresses'] == 1) {
      $data['email_addresses'] = $mail_to['email_addresses_list'];
    }

    if ($mail_to['users'] == 1) {
      $data['users'] = array_column($mail_to['users_list'], 'target_id');
    }

    return serialize($data);
  }

}
