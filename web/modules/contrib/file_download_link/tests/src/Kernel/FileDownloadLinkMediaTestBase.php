<?php

namespace Drupal\Tests\file_download_link\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Class for testing file_download_link_media formatter.
 *
 * @group file_download_link
 */
abstract class FileDownloadLinkMediaTestBase extends KernelTestBase {

  use UserCreationTrait;
  use MediaTypeCreationTrait;

  /**
   * A test node.
   *
   * @var Drupal\node\Entity\Node
   */
  public $node;

  /**
   * A test media.
   *
   * @var Drupal\media\Entity\Media
   */
  public $media;

  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'system',
    'user',
    'media',
    'node',
    'file',
    'file_download_link',
    'file_download_link_media',
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
    $this->installEntitySchema('media');
    $this->installEntitySchema('media_type');
    $this->setUpCurrentUser(['uid' => 99], ['view media']);
    $this->media = $this->createTestMedia();
    $this->node = $this->createTestNode();
  }

  /**
   * @dataProvider providerTestFileDownloadLinkMedia
   *
   * @param array $settings
   *   The settings to use for the file_download_link formatter.
   * @param array $output
   *   Array of expected output values in three parts: title, options, cache.
   *
   * Note that the structure of $output is different from in FileDownloadLinkTestBase.
   * We do not need to support testing for multivalued fields here, so we do not
   * send $output to a foreach loop the way we do in FileDownloadLinkTestBase.
   */
  public function testFileDownloadLinkMedia(array $settings, array $output): void {
    $render = $this->node->field_media->view([
      'type' => 'file_download_link_media',
      'label' => 'hidden',
      'settings' => $settings,
    ]);
    // Assert cache tags on the top level of the render array.
    $expected_top_level_cache = [
      'tags' => ['media:1'],
      'contexts' => ['user.permissions'],
      'max-age' => -1,
    ];
    $this->assertEqualsCanonicalizing($expected_top_level_cache['tags'], $render[0]['#cache']['tags']);
    $this->assertEqualsCanonicalizing($expected_top_level_cache['contexts'], $render[0]['#cache']['contexts']);
    $this->assertEqualsCanonicalizing($expected_top_level_cache['max-age'], $render[0]['#cache']['max-age']);

    // Assert the deeper part of the render array, where file_download_link is leveraged.
    $this->assertInstanceOf('Drupal\Core\Url', $render[0][0]['#url']);
    $this->assertSame(\Drupal::service('file_url_generator')->generate('public://file.txt')->toString(), $render[0][0]['#url']->toString());
    $this->assertSame($output['title'], $render[0][0]['#title']);
    $this->assertEqualsCanonicalizing($output['options'], $render[0][0]['#options']);
    $this->assertEqualsCanonicalizing($output['cache']['tags'], $render[0][0]['#cache']['tags']);
    $this->assertEqualsCanonicalizing($output['cache']['contexts'], $render[0][0]['#cache']['contexts']);
    $this->assertEqualsCanonicalizing($output['cache']['max-age'], $render[0][0]['#cache']['max-age']);
  }

  /**
   * Data provider for testFileDownloadLinkMedia()
   */
  abstract public static function providerTestFileDownloadLinkMedia();

  /**
   * Helper function to create media that can be used for testing.
   *
   * @return Drupal\media\Entity\Media
   *   A media to be used for testing.
   */
  protected function createTestMedia() {
    $this->createMediaType('file', ['id' => 'test_media', 'label' => 'Test Media']);
    file_put_contents('public://file.txt', str_repeat('t', 10));
    $file_file = File::create([
      'uri' => 'public://file.txt',
      'filename' => 'file.txt',
    ]);
    $file_file->save();
    $media = Media::create(['bundle' => 'test_media', 'name' => 'Test Media']);
    $media->set('field_media_file', $file_file->id());
    $media->set('status', 1);
    $media->save();

    return $media;
  }

  /**
   * Helper function to create node that can be used for testing.
   *
   * @return Drupal\node\Entity\Node
   *   An node to be used for testing.
   */
  protected function createTestNode() {
    $node_type = NodeType::create(['type' => 'test_node', 'name' => 'Test Node']);
    $node_type->save();
    // Our entity will have an image field and a file field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_media',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $field_storage->save();
    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_node',
      'label' => 'Media',
    ]);
    $instance->save();
    $node = Node::create(['type' => 'test_node', 'title' => 'Test Entity']);
    $node->set('field_media', $this->media->id());
    $node->set('status', 1);
    $node->save();

    return $node;
  }

}
