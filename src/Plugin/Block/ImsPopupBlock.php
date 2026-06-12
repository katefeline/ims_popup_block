<?php

namespace Drupal\ims_popup\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides the IMS Popup block plugin.
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
   * {@inheritdoc}
   */
  protected function getTheme(): string {
    return 'ims_popup_block';
  }

  /**
   * {@inheritdoc}
   */
  protected function getLibrary(): array {
    $libraries = ['ims_popup/ims_popup'];

    // Attach brand-specific styling libraries from enabled brand submodules.
    $submodules = ['ims_popup_maggi', 'ims_popup_buitoni', 'ims_popup_thomy'];

    foreach ($submodules as $submodule) {
      if ($this->moduleHandler->moduleExists($submodule)) {
        $libraries[] = $submodule . '/' . $submodule;
      }
    }

    return $libraries;
  }

}
