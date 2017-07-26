<?php

namespace Drupal\clear_caches\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Asset\AssetCollectionOptimizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ClearCacheForm.
 *
 * @package Drupal\clear_caches\Form
 */
class ClearCachesForm extends FormBase {

  /**
   * The class loader.
   *
   * @var \Composer\Autoload\ClassLoader
   */
  protected $classLoader;

  /**
   * The JS optimizer.
   *
   * @var \Drupal\Core\Asset\CssCollectionOptimizer
   */
  protected $optimizerJs;

  /**
   * The CSS optimizer.
   *
   * @var \Drupal\Core\Asset\CssCollectionOptimizer
   */
  protected $optimizerCss;

  /**
   *
   */
  public function __construct($class_loader,
                              AssetCollectionOptimizerInterface $optimizer_js,
                              AssetCollectionOptimizerInterface $optimizer_css
  ) {
    $this->classLoader = $class_loader;
    $this->optimizerJs = $optimizer_js;
    $this->optimizerCss = $optimizer_css;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('class_loader'),
      $container->get('asset.js.collection_optimizer'),
      $container->get('asset.css.collection_optimizer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'clear_caches_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['hints'] = [
      '#type' => 'container',
      'rebuildphp' => [
        '#type' => 'markup',
        '#markup' => $this->t('If clearing the caches fails somehow, you could try to rebuild Drupal outside of itself by calling <a target="_blank" href="@rebuild_php_url">rebuild.php</a>', [
          '@rebuild_php_url' => base_path() . 'core/rebuild.php',
        ]),
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear caches'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->clearCaches();
    drupal_set_message($this->t('The caches have been cleared'));
  }

  /**
   * Performs cache clearing and rebuilding.
   */
  public function clearCaches() {
    // First, clear APC system and user cache.
    if (function_exists('apc_fetch')) {
      apc_clear_cache();
      apc_clear_cache('user');
    }

    // Call drupal_rebuild (includes drupal_flush_all_caches).
    require_once DRUPAL_ROOT . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'utility.inc';
    drupal_rebuild($this->classLoader, $this->getRequest());

    // Flushed aggregated/optimized css/js.
    $this->optimizerJs->deleteAll();
    $this->optimizerCss->deleteAll();
  }

}
