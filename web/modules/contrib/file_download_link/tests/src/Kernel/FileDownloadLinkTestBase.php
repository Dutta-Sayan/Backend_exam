<?php

namespace Drupal\Tests\file_download_link\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Trait for testing file_download_link formatter.
 *
 * Takes care of creating a test node and doing setUp.
 */
abstract class FileDownloadLinkTestBase extends KernelTestBase {

  /**
   * A test entity.
   *
   * @var Drupal\node\Entity\Node
   */
  public $entity;

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'system',
    'user',
    'node',
    'file',
    'file_download_link',
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'field']);
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('node_type');
    $this->entity = $this->createTestEntity();
  }

  /**
   * @dataProvider providerTestFileDownloadLink
   *
   * @param string $field
   *   The name of the field to render. Either field_file or field_image.
   * @param array $settings
   *   The settings to use for the file_download_link formatter.
   * @param array $output
   *   Array of expected output values in three parts: title, options, cache.
   *
   * Please note that to support testing fields with delta > 1, the $output array
   * may have one more level of nesting than you would expect. See the foreach loop
   * below.
   */
  public function testFileDownloadLink(string $field, array $settings, array $output): void {
    $render = $this->entity->{$field}->view([
      'type' => 'file_download_link',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    foreach ($output as $key => $expected) {
      $this->assertInstanceOf('Drupal\Core\Url', $render[$key]['#url']);
      $this->assertSame(\Drupal::service('file_url_generator')->generate($field == 'field_image' ? 'public://file.png' : 'public://file.txt')->toString(), $render[$key]['#url']->toString());
      $this->assertSame($expected['title'], $render[$key]['#title']);
      $this->assertEqualsCanonicalizing($expected['options'], $render[$key]['#options']);
      $this->assertEqualsCanonicalizing($expected['cache']['tags'], $render[$key]['#cache']['tags']);
      $this->assertEqualsCanonicalizing($expected['cache']['contexts'], $render[$key]['#cache']['contexts']);
      $this->assertEqualsCanonicalizing($expected['cache']['max-age'], $render[$key]['#cache']['max-age']);
    }
  }

  /**
   * Data provider for testFileDownloadLink()
   */
  abstract public static function providerTestFileDownloadLink();

  /**
   * Helper function to create entity that can be used for testing.
   *
   * @return Drupal\node\Entity\Node
   *   An entity to be used for testing.
   */
  protected function createTestEntity() {
    $node_type = NodeType::create(['type' => 'test_node', 'name' => 'Test Node']);
    $node_type->save();
    // Our entity will have an image field and a file field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_image',
      'entity_type' => 'node',
      'type' => 'image',
      'cardinality' => -1,
    ]);
    $field_storage->save();
    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_node',
      'label' => 'Image',
    ]);
    $instance->save();
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_file',
      'entity_type' => 'node',
      'type' => 'file',
    ]);
    $field_storage->save();
    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_node',
      'label' => 'File',
    ]);
    $instance->save();

    $image_path = 'core/tests/fixtures/files/image-test.png';
    file_put_contents('public://file.png', file_get_contents($image_path));
    $file_image = File::create([
      'uri' => 'public://file.png',
      'filename' => 'file.png',
    ]);
    $file_image->save();

    file_put_contents('public://file.txt', str_repeat('t', 10));
    $file_file = File::create([
      'uri' => 'public://file.txt',
      'filename' => 'file.txt',
    ]);
    $file_file->save();
    $entity = Node::create(['type' => 'test_node', 'title' => 'Test Entity']);
    $field_image_value = [
      [
        'target_id' => $file_image->id(),
        'alt' => 'This alt text is for the first image.',
      ],
      [
        'target_id' => $file_image->id(),
        'alt' => "When delta is 1 we should see this alt text. Let's add special chars & test them!",
      ],
    ];
    $entity->set('field_image', $field_image_value);
    $entity->set('field_file', $file_file->id());
    $entity->save();

    return $entity;
  }

}
