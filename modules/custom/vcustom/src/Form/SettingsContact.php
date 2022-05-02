<?php

namespace Drupal\vcustom\Form;

use Drupal\vbase\Form\ConfigTypedFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure contact information for this site.
 */
class SettingsContact extends ConfigTypedFormBase {

  /**
   * {@inheritdoc}
   */
  protected $configName = 'vcustom.settings.contact';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vcustom_settings_contact';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->configName);
    $definition = $this->definition($this->configName);

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t($definition['mapping']['email']['label']),
      '#default_value' => $config->get('email'),
    ];

    $form['copyright'] = [
      '#type' => 'textfield',
      '#title' => $this->t($definition['mapping']['copyright']['label']),
      '#default_value' => $config->get('copyright'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
