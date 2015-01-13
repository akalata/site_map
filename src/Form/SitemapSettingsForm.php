<?php

/**
 * @file
 * Contains \Drupal\site_map\Form\SitemapSettingsForm.
 */

namespace Drupal\site_map\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;
use Drupal\book\BookManagerInterface;
use Drupal\Core\Url;

/**
 * Provides a configuration form for sitemap.
 */
class SitemapSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The book manager.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Constructs a SitemapSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactory $config_factory, ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $module_handler = $container->get('module_handler');
    $form = new static(
      $container->get('config.factory'),
      $module_handler
    );
    if ($module_handler->moduleExists('book')) {
      $form->setBookManager($container->get('book.manager'));
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'site_map_settings';
  }

  /**
   * Set book manager service.
   *
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   Book manager service to set.
   */
  public function setBookManager(BookManagerInterface $book_manager) {
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('site_map.settings');

    $form['site_map_page_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#default_value' => $config->get('site_map_page_title'),
      '#description' => $this->t('Page title that will be used on the <a href="@link">site map page</a>.', array('@link' => Url::fromRoute('site_map.page'))),
    );

    $site_map_message = $config->get('site_map_message');
    $form['site_map_message'] = array(
      '#type' => 'text_format',
      '#format' => isset($site_map_message['format']) ? $site_map_message['format'] : NULL,
      '#title' => $this->t('Site map message'),
      '#default_value' => $site_map_message['value'],
      '#description' => $this->t('Define a message to be displayed above the site map.'),
    );

    $form['site_map_content'] = array(
      '#type' => 'details',
      '#title' => $this->t('Site map content'),
    );
    $site_map_ordering = array();
    $form['site_map_content']['site_map_show_front'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show front page'),
      '#default_value' => $config->get('site_map_show_front'),
      '#description' => $this->t('When enabled, this option will include the front page in the site map.'),
    );
    $site_map_ordering['front'] = t('Front page');
    $form['site_map_content']['site_map_show_titles'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show titles'),
      '#default_value' => $config->get('site_map_show_titles'),
      '#description' => $this->t('When enabled, this option will show titles. Disable to not show section titles.'),
    );

    if ($this->moduleHandler->moduleExists('blog')) {
      $form['site_map_content']['site_map_show_blogs'] = array(
        '#type' => 'checkbox',
        '#title' => t('Show active blog authors'),
        '#default_value' => $config->get('site_map_show_blogs'),
        '#description' => t('When enabled, this option will show the 10 most active blog authors.'),
      );
      $site_map_ordering['blogs'] = t('Active blog authors');
    }

    if ($this->moduleHandler->moduleExists('book')) {
      $book_options = array();
      foreach ($this->bookManager->getAllBooks() as $book) {
        $book_options[$book['bid']] = $book['title'];
      }
      $form['site_map_content']['site_map_show_books'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Books to include in the site map'),
        '#default_value' => $config->get('site_map_show_books'),
        '#options' => $book_options,
        '#multiple' => TRUE,
      );
      $form['site_map_content']['site_map_books_expanded'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Show books expanded'),
        '#default_value' => $config->get('site_map_books_expanded'),
        '#description' => $this->t('When enabled, this option will show all children pages for each book.'),
      );
      $site_map_ordering['books'] = t('Books');
    }

    $menu_options = array();
    $menus = Menu::loadMultiple();
    foreach ($menus as $id => $menu) {
      $menu_options[$id] = $menu->label();
      $site_map_ordering['menus_' . $id] = $menu->label();
    }
    $form['site_map_content']['site_map_show_menus'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Menus to include in the site map'),
      '#default_value' => $config->get('site_map_show_menus'),
      '#options' => $menu_options,
    );
    // Thanks for fix by zhuber at
    // https://drupal.org/node/1331104#comment-5200266.
    $form['site_map_content']['site_map_show_menus_hidden'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show disabled menu items'),
      '#default_value' => $config->get('site_map_show_menus_hidden'),
      '#description' => $this->t('When enabled, hidden menu links will also be shown.'),
    );

    if ($this->moduleHandler->moduleExists('faq')) {
      $form['site_map_content']['site_map_show_faq'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Show FAQ content'),
        '#default_value' => $config->get('site_map_show_faq'),
        '#description' => $this->t('When enabled, this option will include the content from the FAQ module in the site map.'),
      );
      $site_map_ordering['faq'] = t('FAQ content');
    }

    if ($this->moduleHandler->moduleExists('taxonomy')) {
      $vocab_options = array();
      foreach (taxonomy_vocabulary_load_multiple() as $vocabulary) {
        $vocab_options[$vocabulary->id()] = $vocabulary->label();
        $site_map_ordering['vocabularies_' . $vocabulary->id()] = $vocabulary->label();
      }
      $form['site_map_content']['site_map_show_vocabularies'] = array(
        '#type' => 'checkboxes',
        '#title' => $this->t('Categories to include in the site map'),
        '#default_value' => $config->get('site_map_show_vocabularies'),
        '#options' => $vocab_options,
        '#multiple' => TRUE,
      );
    }

    $form['site_map_content']['site_map_order'] = array(
      '#type' => 'item',
      '#title' => t('Site map order'),
      '#theme' => 'site_map_order',
    );
    $site_map_order_defaults = $config->get('site_map_order');
    foreach ($site_map_ordering as $content_id => $content_title) {
      $form['site_map_content']['site_map_order'][$content_id] = array(
        'content' => array(
          '#markup' => $content_title,
        ),
        'weight' => array(
          '#type' => 'weight',
          '#title' => t('Weight for @title', array('@title' => $content_title)),
          '#title_display' => 'invisible',
          '#delta' => 50,
          '#default_value' => isset($site_map_order_defaults[$content_id]) ? $site_map_order_defaults[$content_id] : -50,
          '#parents' => array('site_map_order', $content_id),
        ),
        '#weight' => isset($site_map_order_defaults[$content_id]) ? $site_map_order_defaults[$content_id] : -50,
      );
    }

    $form['site_map_taxonomy_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Categories settings'),
    );
    $form['site_map_taxonomy_options']['site_map_show_description'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show category description'),
      '#default_value' => $config->get('site_map_show_description'),
      '#description' => $this->t('When enabled, this option will show the category description.'),
    );
    $form['site_map_taxonomy_options']['site_map_show_count'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show node counts by categories'),
      '#default_value' => $config->get('site_map_show_count'),
      '#description' => $this->t('When enabled, this option will show the number of nodes in each taxonomy term.'),
    );
    $form['site_map_taxonomy_options']['site_map_categories_depth'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Categories depth'),
      '#default_value' => $config->get('site_map_categories_depth'),
      '#size' => 3,
      '#maxlength' => 10,
      '#description' => $this->t('Specify how many categories and subcategories should be included. Enter "-1" to include all categories and subcategories, "0" not to include categories at all, or "1" not to include subcategories.'),
    );
    $form['site_map_taxonomy_options']['site_map_term_threshold'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Category count threshold'),
      '#default_value' => $config->get('site_map_term_threshold'),
      '#size' => 3,
      '#description' => $this->t('Only show categories whose node counts are greater than this threshold. Set to -1 to disable.'),
    );
    $form['site_map_taxonomy_options']['site_map_forum_threshold'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Forum count threshold'),
      '#default_value' => $config->get('site_map_forum_threshold'),
      '#size' => 3,
      '#description' => $this->t('Only show forums whose node counts are greater than this threshold. Set to -1 to disable.'),
    );

    $form['site_map_rss_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('RSS settings'),
    );
    $form['site_map_rss_options']['site_map_rss_front'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('RSS feed for front page'),
      '#default_value' => $config->get('site_map_rss_front'),
      '#description' => $this->t('The RSS feed for the front page, default is rss.xml.'),
    );
    $form['site_map_rss_options']['site_map_show_rss_links'] = array(
      '#type' => 'select',
      '#title' => $this->t('Include RSS links'),
      '#default_value' => $config->get('site_map_show_rss_links'),
      '#options' => array(
        0 => $this->t('None'),
        1 => $this->t('Include on the right side'),
        2 => $this->t('Include on the left side'),
      ),
      '#description' => $this->t('When enabled, this option will show links to the RSS feeds for each category and blog.'),
    );
    $form['site_map_rss_options']['site_map_rss_depth'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('RSS feed depth'),
      '#default_value' => $config->get('site_map_rss_depth'),
      '#size' => 3,
      '#maxlength' => 10,
      '#description' => $this->t('Specify how many RSS feed links should be included. Enter "-1" to include with all categories and subcategories, "0" not to include with any categories or subcategories, or "1" not to include with subcategories only.'),
    );

    $form['site_map_css_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('CSS settings'),
    );
    $form['site_map_css_options']['site_map_css'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Do not include site map CSS file'),
      '#default_value' => $config->get('site_map_css'),
      '#description' => $this->t("If you don't want to load the included CSS file you can check this box."),
    );

    // Make use of the Checkall module if it's installed.
    if ($this->moduleHandler->moduleExists('checkall')) {
      $form['site_map_content']['site_map_show_books']['#checkall'] = TRUE;
      $form['site_map_content']['site_map_show_menus']['#checkall'] = TRUE;
      $form['site_map_content']['site_map_show_vocabularies']['#checkall'] = TRUE;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->configFactory->get('site_map.settings');

    $config->set('site_map_page_title', $values['site_map_page_title'])
      ->set('site_map_message.value', $values['site_map_message']['value'])
      ->set('site_map_message.format', $values['site_map_message']['format'])
      ->set('site_map_show_front', $values['site_map_show_front'])
      ->set('site_map_show_titles', $values['site_map_show_titles'])
      ->set('site_map_show_menus', array_filter($values['site_map_show_menus']))
      ->set('site_map_show_menus_hidden', $values['site_map_show_menus_hidden'])
      ->set('site_map_show_vocabularies', array_filter($values['site_map_show_vocabularies']))
      ->set('site_map_show_description', $values['site_map_show_description'])
      ->set('site_map_show_count', $values['site_map_show_count'])
      ->set('site_map_categories_depth', $values['site_map_categories_depth'])
      ->set('site_map_term_threshold', $values['site_map_term_threshold'])
      ->set('site_map_forum_threshold', $values['site_map_forum_threshold'])
      ->set('site_map_rss_front', $values['site_map_rss_front'])
      ->set('site_map_show_rss_links', $values['site_map_show_rss_links'])
      ->set('site_map_rss_depth', $values['site_map_rss_depth'])
      ->set('site_map_css', $values['site_map_css'])
      ->set('site_map_order', $values['site_map_order']);

    if ($this->moduleHandler->moduleExists('book')) {
      $config->set('site_map_show_books', array_filter($values['site_map_show_books']))
        ->set('site_map_books_expanded', $values['site_map_books_expanded']);
    }

    if ($this->moduleHandler->moduleExists('faq')) {
      $config->set('site_map_show_faq', $values['site_map_show_faq']);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
