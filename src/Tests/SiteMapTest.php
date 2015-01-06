<?php

/**
 * @file
 * Contains \Drupal\site_map\Tests\SiteMapTest.
 */

namespace Drupal\site_map\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Test case class for sitemap tests.
 *
 * @group site_map
 */
class SiteMapTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('site_map', 'node', 'menu_ui', 'taxonomy');

  protected function setUp() {
    parent::setUp();

    // Create content type.
    $this->drupalCreateContentType(array(
      'type' => 'page',
      'name' => 'Basic page',
    ));

    // Create filter format.
    $restricted_html_format = entity_create('filter_format', array(
      'format' => 'restricted_html',
      'name' => 'Restricted HTML',
      'filters' => array(
        'filter_html' => array(
          'status' => TRUE,
          'weight' => -10,
          'settings' => array(
            'allowed_html' => '<p> <br> <strong> <a> <em> <h4>',
          ),
        ),
        'filter_autop' => array(
          'status' => TRUE,
          'weight' => 0,
        ),
        'filter_url' => array(
          'status' => TRUE,
          'weight' => 0,
        ),
        'filter_htmlcorrector' => array(
          'status' => TRUE,
          'weight' => 10,
        ),
      ),
    ));
    $restricted_html_format->save();

    // Create user then login.
    $this->user = $this->drupalCreateUser(array(
      'administer site configuration',
      'access site map',
      'administer menu',
      'administer nodes',
      'create page content',
      $restricted_html_format->getPermissionName(),
    ));
    $this->drupalLogin($this->user);
  }

  /**
   * Tests page title.
   */
  public function testPageTitle() {
    // Assert default page title.
    $this->drupalGet('/sitemap');
    $this->assertTitle('Site map | Drupal', 'The title on the site map page is "Site map | Drupal".');

    // Change page title.
    $newTitle = $this->randomMachineName();
    $edit = array(
      'site_map_page_title' => $newTitle,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that page title is changed.
    $this->drupalGet('/sitemap');
    $this->assertTitle("$newTitle | Drupal", 'The title on the site map page is "' . "$newTitle | Drupal" . '".');
  }

  /**
   * Tests site map message.
   */
  public function testSiteMapMessage() {
    // Assert that site map message is not included in the site map by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect('.site-map-message');
    $this->assertEqual(count($elements), 0, 'Site map message is not included.');

    // Change site map message.
    $newMessage = $this->randomMachineName(16);
    $edit = array(
      'site_map_message[value]' => $newMessage,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert site map message is included in the site map.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-message:contains('" . $newMessage . "')");
    $this->assertEqual(count($elements), 1, 'Site map message is included.');
  }

  /**
   * Tests front page.
   */
  public function testFrontPage() {
    // Assert that front page is included in the site map by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . t('Front page') . "')");
    $this->assertEqual(count($elements), 1, 'Front page is included.');

    // Configure module not to show front page.
    $edit = array(
      'site_map_show_front' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that front page is not included in the site map.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . t('Front page') . "')");
    $this->assertEqual(count($elements), 0, 'Front page is not included.');
  }

  /**
   * Tests titles.
   */
  public function testTitles() {
    // Assert that titles are included in the site map by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect('.site-map-box h2');
    $this->assertTrue(count($elements) > 0, 'Titles are included.');

    // Configure module not to show titles.
    $edit = array(
      'site_map_show_titles' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that titles are not included in the site map.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect('.site-map-box h2');
    $this->assertEqual(count($elements), 0, 'Section titles are not included.');
  }

  /**
   * Tests menus.
   */
  public function testMenus() {
    // Assert that main menu is not included in the site map by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . t('Main navigation') . "')");
    $this->assertEqual(count($elements), 0, 'Main menu is not included.');

    // Configure module to show main menu, with enabled menu items only.
    $edit = array(
      'site_map_show_menus[main]' => 'main',
      'site_map_show_menus_hidden' => FALSE
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Create dummy node with enabled menu item.
    $node_1_title = $this->randomString();
    $edit = array(
      'title[0][value]' => $node_1_title,
      'menu[enabled]' => TRUE,
      'menu[title]' => $node_1_title,
      // In oder to make main navigation menu displayed, there must be at least
      // one child menu item of that menu.
      'menu[menu_parent]' => 'main:',
    );
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));

    // Create dummy node with disabled menu item.
    $node_2_title = $this->randomString();
    $edit = array(
      'title[0][value]' => $node_2_title,
      'menu[enabled]' => TRUE,
      'menu[title]' => $node_2_title,
      'menu[menu_parent]' => 'main:',
    );
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));

    // Disable menu item.
    $menu_links = entity_load_multiple_by_properties('menu_link_content', array('title' => $node_2_title));
    $menu_link = reset($menu_links);
    $mlid = $menu_link->id();
    $edit = array(
      'enabled[value]' => FALSE,
    );
    $this->drupalPostForm("admin/structure/menu/item/$mlid/edit", $edit, t('Save'));

    // Assert that main menu is included in the site map.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . t('Main navigation') . "')");
    $this->assertEqual(count($elements), 1, 'Main menu is included.');

    // Assert that node 1 is listed in the site map, but not node 2.
    $this->assertLink($node_1_title);
    $this->assertNoLink($node_2_title);

    // Configure module to show all menu items.
    $edit = array(
      'site_map_show_menus_hidden' => TRUE
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that both node 1 and node 2 are listed in the site map.
    $this->drupalGet('/sitemap');
    $this->assertLink($node_1_title);
    $this->assertLink($node_2_title);
  }

  /**
   * Create taxonomy term reference field for testing categories.
   *
   * @return string
   *   Created field name.
   */
  protected function createTaxonomyTermReferenceField() {
    // Create a new vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => 'Tags',
      'vid' => 'tags',
    ));
    $vocabulary->save();

    // Create new taxonomy term reference field.
    $field_tags_name = Unicode::strtolower($this->randomMachineName());
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $field_tags_name,
      'entity_type' => 'node',
      'type' => 'taxonomy_term_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => array(
        'allowed_values' => array(
          array(
            'vocabulary' => $vocabulary->id(),
            'parent' => '0',
          ),
        ),
      )
    ));
    $field_storage->save();
    entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => 'page',
    ))->save();
    entity_get_form_display('node', 'page', 'default')
      ->setComponent($field_tags_name, array(
        'type' => 'taxonomy_autocomplete',
      ))
      ->save();
    entity_get_display('node', 'page', 'full')
      ->setComponent($field_tags_name, array(
        'type' => 'taxonomy_term_reference_link',
      ))
      ->save();

    return $field_tags_name;
  }

  /**
   * Tests categories.
   */
  public function testCategories() {
    $tags = array(
      $this->randomMachineName(),
      $this->randomMachineName(),
      $this->randomMachineName(),
    );
    $field_tags_name = $this->createTaxonomyTermReferenceField();

    // Assert that 'Tags' category is not included in the site map by default.
    $this->drupalGet('/sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . t('Tags') . "')");
    $this->assertEqual(count($elements), 0, 'Tags category is not included.');

    // Assert that no tags are listed in the site map.
    foreach ($tags as $tag) {
      $this->assertNoLink($tag);
    }

    // Configure module to show 'Tags' category.
    $edit = array(
      'site_map_show_vocabularies[tags]' => TRUE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Create dummy node.
    $title = $this->randomString();
    $edit = array(
      'title[0][value]' => $title,
      'menu[enabled]' => TRUE,
      'menu[title]' => $title,
      $field_tags_name => implode(',', $tags),
    );
    $this->drupalPostForm('node/add/page', $edit, t('Save and publish'));

    // Assert that 'Tags' category is included in the site map.
    $this->drupalGet('sitemap');
    $elements = $this->cssSelect(".site-map-box h2:contains('" . t('Tags') . "')");
    $this->assertEqual(count($elements), 1, 'Tags category is included.');

    // Assert that all tags are listed in the site map.
    foreach ($tags as $tag) {
      $this->assertLink($tag);
    }
  }
}
