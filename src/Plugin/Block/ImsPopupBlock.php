<?php

namespace Drupal\ims_popup\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a 'Ims Popup' Block.
 */
#[Block(
  id: "ims_popup_block",
  admin_label: new TranslatableMarkup("IMS Popup block"),
  category: new TranslatableMarkup("IMS Popup")
)]
class ImsPopupBlock extends ImsPopupBaseBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->preBuild();
  }

  /**
   * {@inheritDoc}
   */
  protected function getTheme(): string {
    return 'ims_popup_block';
  }

  /**
   * {@inheritDoc}
   */
  protected function getLibrary(): array {
    $libraries = ['ims_popup/ims_popup'];

    // Check for enabled submodules and add their libraries.
    $enabled_modules = \Drupal::moduleHandler()->getModuleList();
    $submodules = ['ims_popup_maggi', 'ims_popup_buitoni', 'ims_popup_thomy', 'ims_popup_paragraph'];

    foreach ($submodules as $submodule) {
      if (isset($enabled_modules[$submodule])) {
        $libraries[] = $submodule . '/' . $submodule;
      }
    }

    return $libraries;
  }

}
