<?php

namespace Drupal\newsletter2go\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Newsletter2Go subscription form block.
 *
 * @Block(
 *   id = "newsletter2go",
 *   admin_label = @Translation("Newsletter2Go"),
 * )
 */
class Form extends BlockBase implements ContainerFactoryPluginInterface {

  /** @var  ContainerInterface */
  protected $container;

  public function __construct(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $this->container = $container;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // By default, the block will contain 10 feed items.
    return [
      'block_count' => 10,
      'feed' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access newsletter2go content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $feeds = $this->feedStorage->loadMultiple();
    $options = [];
    foreach ($feeds as $feed) {
      $options[$feed->id()] = $feed->label();
    }
    $form['feed'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the feed that should be displayed'),
      '#default_value' => $this->configuration['feed'],
      '#options' => $options,
    ];
    $range = range(2, 20);
    $form['block_count'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of news items in block'),
      '#default_value' => $this->configuration['block_count'],
      '#options' => array_combine($range, $range),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['block_count'] = $form_state->getValue('block_count');
    $this->configuration['feed'] = $form_state->getValue('feed');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $formUniqueCode = variable_get('newsletter2go_formUniqueCode');
    $n2gConfig = variable_get('newsletter2go_widgetStyleConfig');

    $formType = variable_get('newsletter2go_formType');
    if (empty($type)) {
      !empty($formType) ? $type = $formType : $type = 'subscribe';
    }

    empty($func) ? $func = 'createForm' : '';
    $uniqueId = uniqid();

    $params = "'$type:$func', " . $n2gConfig;
    if ($func == 'createPopup') {
      $params .= ", 5"; // '5' seconds delay
    }

    $block['subject'] = t('Newsletter2Go');
    $block['content'] = '<script id="' . ($uniqueId ? $uniqueId : "n2g_script") . '">
     !function(e,t,n,c,r,a,i){e.Newsletter2GoTrackingObject=r,e[r]=e[r]||function(){(e[r].q=e[r].q||[]).push(arguments)},e[r].l=1*new Date,a=t.createElement(n),i=t.getElementsByTagName(n)[0],a.async=1,a.src=c,i.parentNode.insertBefore(a,i)}(window,document,"script","//static.newsletter2go.com/utils.js","n2g");
     n2g(\'create\',\'' . $formUniqueCode . '\');
     n2g(' . $params . '' . ($uniqueId ? ',"' . $uniqueId . '"' : "") . ');
     </script>';

    return $block;
  }

  /**
   * Cache the form per user role.
   *
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $user_roles = \Drupal::currentUser()->getRoles();
    $role_tag = [];
    foreach (array_keys($user_roles) as $key) {
      $role_tag[] = 'role:' . $key;
    }
    return Cache::mergeTags($cache_tags, $role_tag);
  }

}
