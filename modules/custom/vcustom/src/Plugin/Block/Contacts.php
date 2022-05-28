<?php

namespace Drupal\vcustom\Plugin\Block;

use Drupal\vbase\Plugin\Block\ConfigBlockBase;

/**
 * Provides a 'Contacts'
 *
 * @Block(
 *   id = "vcustom_contacts",
 *   admin_label = "Contacts"
 * )
 */
class Contacts extends ConfigBlockBase {

  /**
   * {@inheritdoc}
   */
  protected $configName = 'vcustom.settings.contact';

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->configFactory->get($this->configName);

    //$build['text'] = [
    //  '#type' => 'processed_text',
    //  '#text' => $config->get('text.value'),
    //  '#format' => $config->get('text.format'),
    //];

    $build['copyright'] = [
      '#markup' => $config->get('copyright'),
    ];

    return $build;
  }

}
