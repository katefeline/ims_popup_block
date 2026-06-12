<?php

namespace Drupal\ims_popup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract Class for IMS Popup blocks.
 */
abstract class ImsPopupBaseBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;


  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $currentRouteMatch;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Class constructor for block.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $current_route_match, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentRouteMatch = $current_route_match;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('module_handler'),
    );
  }

  /**
   * Method for preparing data before build block process.
   */
  protected function preBuild() {
    $node = NULL;
    if ($this->currentRouteMatch !== NULL) {
      $node = $this->currentRouteMatch->getParameter('node');
    }

    $has_popup_paragraph = FALSE;
    if ($node instanceof NodeInterface) {
      foreach ($node->getFieldDefinitions() as $field_name => $field_definition) {
        if ($field_definition->getType() === 'entity_reference_revisions' && $field_definition->getSetting('target_type') === 'paragraph') {
          $paragraphs = $node->get($field_name)->referencedEntities();
          foreach ($paragraphs as $paragraph) {
            if ($paragraph->bundle() === 'ims_popup') {
              $has_popup_paragraph = TRUE;
              break 2;
            }
          }
        }
      }
    }

    if ($has_popup_paragraph && $node instanceof NodeInterface) {
      return $this->noRender($node);
    }

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
    $ims_popup_image = $this->buildMediaImageRenderArray(
      $ims_popup_image_mid,
      $ims_popup_image_alt,
      $ims_popup_image_title
    );

    // Process logo media entity.
    $ims_popup_logo = $this->buildMediaImageRenderArray(
      $ims_popup_logo_mid,
      $ims_popup_logo_alt,
      $ims_popup_logo_title
    );

    // Build render array for the block.
    return [
      '#theme' => $this->getTheme(),
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
        'library' => $this->getLibrary(),
      ],
      '#cache' => [
        'tags' => array_filter([
          $ims_popup_image_mid ? 'media:' . $ims_popup_image_mid : NULL,
          $ims_popup_logo_mid ? 'media:' . $ims_popup_logo_mid : NULL,
          $node instanceof NodeInterface ? 'node:' . $node->id() : NULL,
        ]),
        'contexts' => ['languages', 'route'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  /**
   * Method contains render array without values.
   *
   * @param \Drupal\node\NodeInterface|null $node
   *   The current node entity.
   *
   * @return array
   *   Minimum data for empty return.
   */
  protected function noRender(?NodeInterface $node = NULL): array {
    return [
      '#markup' => '',
      '#cache' => [
        'tags' => $node instanceof NodeInterface ? ['node:' . $node->id()] : [],
        'contexts' => ['route'],
        'max-age' => Cache::PERMANENT,
      ],
    ];
  }

  /**
   * Build media image render array.
   *
   * @param mixed $media_id
   *   The media entity ID.
   * @param string $custom_alt
   *   Custom alt text.
   * @param string $custom_title
   *   Custom title text.
   *
   * @return array|null
   *   The render array or NULL.
   */
  protected function buildMediaImageRenderArray($media_id, string $custom_alt = '', string $custom_title = ''): ?array {
    if (empty($media_id)) {
      return NULL;
    }

    $media = $this->entityTypeManager->getStorage('media')->load($media_id);

    if ($media && $media->bundle() === 'image' && $media->hasField('field_media_image')) {
      $image_field = $media->get('field_media_image');

      if (!$image_field->isEmpty() && ($file = $image_field->entity)) {
        return [
          '#theme' => 'image',
          '#uri' => $file->getFileUri(),
          '#alt' => $custom_alt ?: ($image_field->getValue()[0]['alt'] ?? ''),
          '#title' => $custom_title ?: $media->label(),
        ];
      }
    }

    return NULL;
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
    $form['ims_popup_image_mid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'media',
      '#selection_settings' => [
        'target_bundles' => ['image'],
      ],
      '#title' => $this->t('Popup Image'),
      '#description' => $this->t('Select an image media entity for the popup.'),
      '#default_value' => !empty($this->configuration['ims_popup_image_mid']) ? $this->entityTypeManager->getStorage('media')->load($this->configuration['ims_popup_image_mid']) : NULL,
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
    $form['ims_popup_logo_mid'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'media',
      '#selection_settings' => [
        'target_bundles' => ['image'],
      ],
      '#title' => $this->t('Popup Logo'),
      '#description' => $this->t('Select an image media entity for the popup logo.'),
      '#default_value' => !empty($this->configuration['ims_popup_logo_mid']) ? $this->entityTypeManager->getStorage('media')->load($this->configuration['ims_popup_logo_mid']) : NULL,
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
      '#type' => 'checkbox',
      '#title' => $this->t('Open link in new tab'),
      '#default_value' => ($this->configuration['ims_popup_link_target'] ?? '') === '_blank' ? 1 : 0,
      '#description' => $this->t('If checked, the link will open in a new tab. Otherwise, it opens in the current page.'),
    ];
    $form['ims_popup_timer'] = [
      '#type' => 'number',
      '#title' => $this->t('Popup Timer (seconds)'),
      '#default_value' => $this->configuration['ims_popup_timer'] ?? 1,
      '#min' => 1,
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
    // Validate image alt/title if image selected.
    $image_mid = $form_state->getValue('ims_popup_image_mid');
    $has_image = !empty($image_mid);
    if ($has_image) {
      $alt = trim((string) $form_state->getValue('ims_popup_image_alt'));
      $title = trim((string) $form_state->getValue('ims_popup_image_title'));
      if ($alt === '') {
        $form_state->setErrorByName('ims_popup_image_alt', $this->t('Alt text is required for the selected image.'));
      }
      if ($title === '') {
        $form_state->setErrorByName('ims_popup_image_title', $this->t('Image title is required for the selected image.'));
      }
    }
    // Validate logo alt/title if logo selected.
    $logo_mid = $form_state->getValue('ims_popup_logo_mid');
    $has_logo = !empty($logo_mid);
    if ($has_logo) {
      $alt = trim((string) $form_state->getValue('ims_popup_logo_alt'));
      $title = trim((string) $form_state->getValue('ims_popup_logo_title'));
      if ($alt === '') {
        $form_state->setErrorByName('ims_popup_logo_alt', $this->t('Alt text is required for the selected logo.'));
      }
      if ($title === '') {
        $form_state->setErrorByName('ims_popup_logo_title', $this->t('Logo title is required for the selected logo.'));
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

    // Store the selected media entity ID for image.
    $image_mid = $form_state->getValue('ims_popup_image_mid');
    $this->configuration['ims_popup_image_mid'] = !empty($image_mid) ? $image_mid : NULL;

    // Store the selected media entity ID for logo.
    $logo_mid = $form_state->getValue('ims_popup_logo_mid');
    $this->configuration['ims_popup_logo_mid'] = !empty($logo_mid) ? $logo_mid : NULL;
    $this->configuration['ims_popup_link_url'] = $form_state->getValue('ims_popup_link_url');
    $this->configuration['ims_popup_link_text'] = $form_state->getValue('ims_popup_link_text');
    $this->configuration['ims_popup_link_target'] = $form_state->getValue('ims_popup_link_target') ? '_blank' : '';
    $this->configuration['ims_popup_timer'] = $form_state->getValue('ims_popup_timer');
    $this->configuration['ims_popup_background'] = $form_state->getValue('ims_popup_background');
    $this->configuration['ims_popup_repeat'] = $form_state->getValue('ims_popup_repeat');
  }

  /**
   * {@inheritdoc}
   */
  abstract public function build();

  /**
   * Theme getter for block render.
   *
   * @return string
   *   The theme name for render array #theme key.
   */
  abstract protected function getTheme(): string;

  /**
   * Library getter for block render.
   *
   * @return array
   *   The library(s) name for render array attachments.
   */
  abstract protected function getLibrary(): array;

}
