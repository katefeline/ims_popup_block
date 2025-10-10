<?php

namespace Drupal\ims_popup\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Provides a 'Ims Popup' Block.
 */


#[Block(
  id: "ims_popup_block",
  admin_label: new TranslatableMarkup("IMS Popup block"),
  category: new TranslatableMarkup("IMS Popup")
)]

class ImsPopupBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * Entity type manager service.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * File URL generator service.
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, FileUrlGeneratorInterface $file_url_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_url_generator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Extract configuration values matching ims_popup variables.
    $ims_popup_title = $this->configuration['ims_popup_title'] ?? '';
    $ims_popup_text = $this->configuration['ims_popup_text'] ?? '';
    $ims_popup_image_mid = $this->configuration['ims_popup_image_mid'] ?? NULL;
    $ims_popup_image_title = $this->configuration['ims_popup_image_title'] ?? '';
    $ims_popup_image_alt = $this->configuration['ims_popup_image_alt'] ?? '';
    $ims_popup_logo_mid = $this->configuration['ims_popup_logo_mid'] ?? NULL;
    $ims_popup_logo_title = $this->configuration['ims_popup_logo_title'] ?? '';
    $ims_popup_logo_alt = $this->configuration['ims_popup_logo_alt'] ?? '';
    $ims_popup_link_url = $this->configuration['ims_popup_link_url'] ?? '';
    $ims_popup_link_text = $this->configuration['ims_popup_link_text'] ?? '';
    $ims_popup_link_target = $this->configuration['ims_popup_link_target'] ?? '';
    $ims_popup_timer = $this->configuration['ims_popup_timer'] ?? '';
    $ims_popup_background = $this->configuration['ims_popup_background'] ?? '';
    $ims_popup_repeat = $this->configuration['ims_popup_repeat'] ?? '';

    // Process image media entity.
    $ims_popup_image = NULL;
    if (!empty($ims_popup_image_mid)) {
      $media = $this->entityTypeManager->getStorage('media')->load($ims_popup_image_mid);
      if ($media && $media->bundle() === 'image' && $media->hasField('field_media_image')) {
        $image_field = $media->get('field_media_image');
        if (!$image_field->isEmpty() && ($file = $image_field->entity)) {
          $ims_popup_image = [
            '#theme' => 'image',
            '#uri' => $file->getFileUri(),
            '#alt' => $ims_popup_image_alt ?: ($image_field->getValue()[0]['alt'] ?? ''),
            '#title' => $ims_popup_image_title ?: $media->label(),
          ];
        }
      }
    }

    // Process logo media entity.
    $ims_popup_logo = NULL;
    if (!empty($ims_popup_logo_mid)) {
      $media = $this->entityTypeManager->getStorage('media')->load($ims_popup_logo_mid);
      if ($media && $media->bundle() === 'image' && $media->hasField('field_media_image')) {
        $logo_field = $media->get('field_media_image');
        if (!$logo_field->isEmpty() && ($file = $logo_field->entity)) {
          $ims_popup_logo = [
            '#theme' => 'image',
            '#uri' => $file->getFileUri(),
            '#alt' => $ims_popup_logo_alt ?: ($logo_field->getValue()[0]['alt'] ?? ''),
            '#title' => $ims_popup_logo_title ?: $media->label(),
          ];
        }
      }
    }

    // Build render array for the block, pass variables directly.
    $build = [
      '#theme' => 'ims_popup_block',
      '#ims_popup_title' => $ims_popup_title,
      '#ims_popup_text' => $ims_popup_text,
      '#ims_popup_image' => $ims_popup_image,
      '#ims_popup_image_title' => $ims_popup_image_title,
      '#ims_popup_image_alt' => $ims_popup_image_alt,
      '#ims_popup_logo' => $ims_popup_logo,
      '#ims_popup_logo_title' => $ims_popup_logo_title,
      '#ims_popup_logo_alt' => $ims_popup_logo_alt,
      '#ims_popup_link_url' => $ims_popup_link_url,
      '#ims_popup_link_text' => $ims_popup_link_text,
      '#ims_popup_link_target' => $ims_popup_link_target,
      '#ims_popup_timer' => $ims_popup_timer,
      '#ims_popup_background' => $ims_popup_background,
      '#ims_popup_repeat' => $ims_popup_repeat,
      '#attached' => [
        'library' => ['ims_popup/ims_popup'],
      ],
      '#cache' => [
        'tags' => array_filter([
          $ims_popup_image_mid ? 'media:' . $ims_popup_image_mid : NULL,
          $ims_popup_logo_mid ? 'media:' . $ims_popup_logo_mid : NULL,
        ]),
        'contexts' => ['languages'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['ims_popup_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Popup Title'),
      '#default_value' => $this->configuration['ims_popup_title'] ?? '',
      '#required' => FALSE,
    ];
    $form['ims_popup_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Popup Text'),
      '#default_value' => $this->configuration['ims_popup_text'] ?? '',
      '#required' => FALSE,
    ];
    $form['ims_popup_image_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Popup Image'),
      '#description' => $this->t('Upload an image for the popup.'),
      '#default_value' => !empty($this->configuration['ims_popup_image_fid']) ? [$this->configuration['ims_popup_image_fid']] : NULL,
      '#upload_location' => 'public://ims_popup_images/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif svg'],
      ],
      '#required' => FALSE,
    ];
    $form['ims_popup_image_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Popup Image Alt Text'),
      '#default_value' => $this->configuration['ims_popup_image_alt'] ?? '',
      '#maxlength' => 255,
      '#required' => FALSE,
    ];
    $form['ims_popup_image_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Popup Image Title'),
      '#default_value' => $this->configuration['ims_popup_image_title'] ?? '',
      '#maxlength' => 255,
      '#required' => FALSE,
    ];
    $form['ims_popup_logo_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Popup Logo'),
      '#description' => $this->t('Upload a logo for the popup.'),
      '#default_value' => !empty($this->configuration['ims_popup_logo_fid']) ? [$this->configuration['ims_popup_logo_fid']] : NULL,
      '#upload_location' => 'public://ims_popup_logos/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif svg'],
      ],
      '#required' => FALSE,
    ];
    $form['ims_popup_logo_alt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Popup Logo Alt Text'),
      '#default_value' => $this->configuration['ims_popup_logo_alt'] ?? '',
      '#maxlength' => 255,
      '#required' => FALSE,
    ];
    $form['ims_popup_logo_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Popup Logo Title'),
      '#default_value' => $this->configuration['ims_popup_logo_title'] ?? '',
      '#maxlength' => 255,
      '#required' => FALSE,
    ];
    $form['ims_popup_link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link URL'),
      '#default_value' => $this->configuration['ims_popup_link_url'] ?? '',
      '#required' => FALSE,
      '#description' => $this->t('Enter a full URL (e.g., https://example.com) or relative path (e.g., /about).'),
    ];
    $form['ims_popup_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $this->configuration['ims_popup_link_text'] ?? '',
      '#required' => FALSE,
    ];
    $form['ims_popup_link_target'] = [
      '#type' => 'select',
      '#title' => $this->t('Link Target'),
      '#options' => [
        '' => $this->t('Open in this page'),
        '_blank' => $this->t('Open in new tab'),
      ],
      '#default_value' => $this->configuration['ims_popup_link_target'] ?? '',
      '#required' => FALSE,
    ];
    $form['ims_popup_timer'] = [
      '#type' => 'number',
      '#title' => $this->t('Popup Timer (seconds)'),
      '#default_value' => $this->configuration['ims_popup_timer'] ?? 0,
      '#min' => 0,
      '#description' => $this->t('Delay before showing the popup (in seconds).'),
      '#required' => FALSE,
    ];
    $form['ims_popup_background'] = [
      '#type' => 'select',
      '#title' => $this->t('Darken Background'),
      '#options' => [
        '' => $this->t('No'),
        'On' => $this->t('Yes'),
      ],
      '#default_value' => $this->configuration['ims_popup_background'] ?? '',
      '#required' => FALSE,
    ];
    $form['ims_popup_repeat'] = [
      '#type' => 'select',
      '#title' => $this->t('Repeat Popup'),
      '#options' => [
        '1' => $this->t('Always show'),
        '0' => $this->t('Show once per session'),
      ],
      '#default_value' => $this->configuration['ims_popup_repeat'] ?? '1',
      '#required' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    // Validate link url (should start from / or https://).
    $link_url = $form_state->getValue('ims_popup_link_url');
    if (!empty($link_url)) {
      if (!filter_var($link_url, FILTER_VALIDATE_URL) && !str_starts_with($link_url, '/')) {
        $form_state->setErrorByName('ims_popup_link_url', $this->t('Please enter a valid URL (e.g., https://example.com) or internal path (e.g., /about).'));
      }
    }
    // Validate link text if link URL is provided.
    $link_text = $form_state->getValue('ims_popup_link_text');
    if (!empty($link_url) && empty($link_text)) {
      $form_state->setErrorByName('ims_popup_link_text', $this->t('Link text is required when a link URL is provided.'));
    }
    // Validate image alt/title if image uploaded.
    $image_fids = (array) $form_state->getValue('ims_popup_image_upload');
    $has_image = !empty($image_fids) && !empty($image_fids[0]);
    if ($has_image) {
      $alt = trim((string) $form_state->getValue('ims_popup_image_alt'));
      $title = trim((string) $form_state->getValue('ims_popup_image_title'));
      if ($alt === '') {
        $form_state->setErrorByName('ims_popup_image_alt', $this->t('Alt text is required for the uploaded image.'));
      }
      if ($title === '') {
        $form_state->setErrorByName('ims_popup_image_title', $this->t('Image title is required for the uploaded image.'));
      }
    }
    // Validate logo alt/title if logo uploaded.
    $logo_fids = (array) $form_state->getValue('ims_popup_logo_upload');
    $has_logo = !empty($logo_fids) && !empty($logo_fids[0]);
    if ($has_logo) {
      $alt = trim((string) $form_state->getValue('ims_popup_logo_alt'));
      $title = trim((string) $form_state->getValue('ims_popup_logo_title'));
      if ($alt === '') {
        $form_state->setErrorByName('ims_popup_logo_alt', $this->t('Alt text is required for the uploaded logo.'));
      }
      if ($title === '') {
        $form_state->setErrorByName('ims_popup_logo_title', $this->t('Logo title is required for the uploaded logo.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['ims_popup_title'] = $form_state->getValue('ims_popup_title');
    $this->configuration['ims_popup_text'] = $form_state->getValue('ims_popup_text');
    $this->configuration['ims_popup_image_alt'] = $form_state->getValue('ims_popup_image_alt');
    $this->configuration['ims_popup_image_title'] = $form_state->getValue('ims_popup_image_title');
    $this->configuration['ims_popup_logo_alt'] = $form_state->getValue('ims_popup_logo_alt');
    $this->configuration['ims_popup_logo_title'] = $form_state->getValue('ims_popup_logo_title');
    // If new image is uploaded, it must be stored.
    $image_fid = $form_state->getValue('ims_popup_image_upload');
    if (!empty($image_fid[0])) {
      $file = $this->entityTypeManager->getStorage('file')->load($image_fid[0]);
      if ($file) {
        $file->setPermanent();
        $file->save();
        $this->configuration['ims_popup_image_fid'] = $file->id();
        $media_storage = $this->entityTypeManager->getStorage('media');
        $media = NULL;
        if (!empty($this->configuration['ims_popup_image_mid'])) {
          $media = $media_storage->load($this->configuration['ims_popup_image_mid']);
        }
        if (!$media) {
          $media = $media_storage->create([
            'bundle' => 'image',
            'name' => $file->getFilename(),
            'field_media_image' => [
              'target_id' => $file->id(),
              'alt' => $this->configuration['ims_popup_image_alt'] ?: $file->getFilename(),
              'title' => $this->configuration['ims_popup_image_title'] ?: $file->getFilename(),
            ],
            'status' => 1,
          ]);
        } else {
          $media->set('field_media_image', [
            'target_id' => $file->id(),
            'alt' => $this->configuration['ims_popup_image_alt'] ?: $file->getFilename(),
            'title' => $this->configuration['ims_popup_image_title'] ?: $file->getFilename(),
          ]);
        }
        $media->set('name', $this->configuration['ims_popup_image_title'] ?: $file->getFilename());
        $media->save();
        $this->configuration['ims_popup_image_mid'] = $media->id();
      }
    } else {
      $this->configuration['ims_popup_image_fid'] = NULL;
      $this->configuration['ims_popup_image_mid'] = NULL;
      $this->configuration['ims_popup_image_alt'] = NULL;
      $this->configuration['ims_popup_image_title'] = NULL;
    }
    // If new logo image is uploaded, it must be stored.
    $logo_fid = $form_state->getValue('ims_popup_logo_upload');
    if (!empty($logo_fid[0])) {
      $file = $this->entityTypeManager->getStorage('file')->load($logo_fid[0]);
      if ($file) {
        $file->setPermanent();
        $file->save();
        $this->configuration['ims_popup_logo_fid'] = $file->id();
        $media_storage = $this->entityTypeManager->getStorage('media');
        $media = NULL;
        if (!empty($this->configuration['ims_popup_logo_mid'])) {
          $media = $media_storage->load($this->configuration['ims_popup_logo_mid']);
        }
        if (!$media) {
          $media = $media_storage->create([
            'bundle' => 'image',
            'name' => $file->getFilename(),
            'field_media_image' => [
              'target_id' => $file->id(),
              'alt' => $this->configuration['ims_popup_logo_alt'] ?: $file->getFilename(),
              'title' => $this->configuration['ims_popup_logo_title'] ?: $file->getFilename(),
            ],
            'status' => 1,
          ]);
        } else {
          $media->set('field_media_image', [
            'target_id' => $file->id(),
            'alt' => $this->configuration['ims_popup_logo_alt'] ?: $file->getFilename(),
            'title' => $this->configuration['ims_popup_logo_title'] ?: $file->getFilename(),
          ]);
        }
        $media->set('name', $this->configuration['ims_popup_logo_title'] ?: $file->getFilename());
        $media->save();
        $this->configuration['ims_popup_logo_mid'] = $media->id();
      }
    } else {
      $this->configuration['ims_popup_logo_fid'] = NULL;
      $this->configuration['ims_popup_logo_mid'] = NULL;
      $this->configuration['ims_popup_logo_alt'] = NULL;
      $this->configuration['ims_popup_logo_title'] = NULL;
    }
    $this->configuration['ims_popup_link_url'] = $form_state->getValue('ims_popup_link_url');
    $this->configuration['ims_popup_link_text'] = $form_state->getValue('ims_popup_link_text');
    $this->configuration['ims_popup_link_target'] = $form_state->getValue('ims_popup_link_target');
    $this->configuration['ims_popup_timer'] = $form_state->getValue('ims_popup_timer');
    $this->configuration['ims_popup_background'] = $form_state->getValue('ims_popup_background');
    $this->configuration['ims_popup_repeat'] = $form_state->getValue('ims_popup_repeat');
  }
}          