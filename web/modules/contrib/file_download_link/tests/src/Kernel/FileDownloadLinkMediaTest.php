<?php

namespace Drupal\Tests\file_download_link\Kernel;

/**
 * Class for testing file_download_link_media formatter.
 *
 * @group file_download_link
 */
class FileDownloadLinkMediaTest extends FileDownloadLinkMediaTestBase {

  /**
   * {@inheritdoc}
   */
  public static function providerTestFileDownloadLinkMedia() {
    return [
      'default settings' => [
        'settings' => [],
        'output' => [
          'title' => 'Download',
          'options' => [
            'attributes' => [
              'class' => [
                'file-download',
                'file-download-text',
                'file-download-plain',
              ],
              'target' => '_blank',
              'download' => TRUE,
            ],
          ],
          'cache' => [
            'tags' => ['file:1'],
            'contexts' => [],
            'max-age' => -1,
          ],
        ],
      ],
      'custom settings' => [
        'settings' => [
          'link_text' => '',
          'link_title' => 'Click to download',
          'new_tab' => FALSE,
          'force_download' => FALSE,
          'custom_classes' => 'Howdy! p@rtner',
        ],
        'output' => [
          'title' => 'file.txt',
          'options' => [
            'attributes' => [
              'class' => [
                'file-download',
                'file-download-text',
                'file-download-plain',
                'Howdy',
                'prtner',
              ],
              'title' => 'Click to download',
            ],
          ],
          'cache' => [
            'tags' => ['file:1'],
            'contexts' => [],
            'max-age' => -1,
          ],
        ],
      ],
    ];
  }

}
