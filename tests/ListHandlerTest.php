<?php

namespace Violinist\AllowListHandler\Tests;

use PHPUnit\Framework\TestCase;
use Violinist\AllowListHandler\AllowListHandler;
use Violinist\Config\Config;
use Wa72\SimpleLogger\ArrayLogger;

class ListHandlerTest extends TestCase
{

    /**
     * @dataProvider getEmptyVariations
     */
    public function testEmptyAllowLists($variation)
    {
        $json = json_decode(file_get_contents(__DIR__ . '/assets/' . $variation . '.json'));
        $config = Config::createFromComposerData($json);
        $items = [
          (object) [
            'name' => 'package1',
          ],
        ];
        $handler = AllowListHandler::createFromConfig($config);
        self::assertEquals($items, $handler->applyToItems($items));
    }

    public function testExactMatches()
    {
        $config = Config::createFromComposerData((object) [
          'extra' => (object) [
            'violinist' => (object) [
              'allow_list' => ['drupal/core']
            ],
          ],
        ]);
        $items = [
          (object) [
            'name' => 'package1',
          ],
          (object) [
            'name' => 'package2',
          ],
          (object) [
            'name' => 'package3',
          ],
          (object) [
            'name' => 'drupal/core',
          ],
        ];
        $handler = AllowListHandler::createFromConfig($config);
        // Should not be the same, for sure.
        self::assertNotEquals($items, $handler->applyToItems($items));
        // Should look like this.
        self::assertEquals([
          (object) [
            'name' => 'drupal/core',
          ],
        ], $handler->applyToItems($items));
        // Or empty it completely, if no items match.
        self::assertEmpty($handler->applyToItems([
          (object) [
            'name' => 'drupal/not-core',
          ],
        ]));
    }

    public function testWildcardMatches()
    {
        $config = Config::createFromComposerData((object) [
          'extra' => (object) [
            'violinist' => (object) [
              'allow_list' => ['drupal/*']
            ],
          ],
        ]);
        $items = [
          (object) [
            'name' => 'package1',
          ],
          (object) [
            'name' => 'package2',
          ],
          (object) [
            'name' => 'package3',
          ],
          (object) [
            'name' => 'drupal/core',
          ],
        ];
        $handler = AllowListHandler::createFromConfig($config);
        // Should not be the same, for sure.
        self::assertNotEquals($items, $handler->applyToItems($items));
        // Should look like this.
        self::assertEquals([
          (object) [
            'name' => 'drupal/core',
          ],
        ], $handler->applyToItems($items));
        // And not empty it completely, in that other case from the last test.
        self::assertNotEmpty($handler->applyToItems([
          (object) [
            'name' => 'drupal/not-core',
          ],
        ]));
        self::assertEmpty($handler->applyToItems([
          (object) [
            'name' => 'not-drupal/not-core',
          ],
        ]));
    }

    public function testMixAndMatch()
    {
        $config = Config::createFromComposerData((object) [
            'extra' => (object) [
                'violinist' => (object) [
                     'allow_list' => [
                         'drupal/*',
                         'not-drupal/core',
                         'sy*/y*ml'
                     ],
                ],
            ],
        ]);
        $items = [
          (object) [
            'name' => 'package1',
          ],
          (object) [
            'name' => 'symfony/yaml',
          ],
          (object) [
            'name' => 'package2',
          ],
          (object) [
            'name' => 'package3',
          ],
          (object) [
            'name' => 'drupal/core',
          ],
        ];
        $handler = AllowListHandler::createFromConfig($config);
        // Should not be the same, for sure.
        self::assertNotEquals($items, $handler->applyToItems($items));
        // Should look like this.
        self::assertEquals([
          (object) [
            'name' => 'symfony/yaml',
          ],
          (object) [
            'name' => 'drupal/core',
          ],
        ], $handler->applyToItems($items));
        // And not empty it completely, in that other case from the last test.
        self::assertNotEmpty($handler->applyToItems([
          (object) [
            'name' => 'drupal/not-core',
          ],
        ]));
        self::assertEmpty($handler->applyToItems([
          (object) [
            'name' => 'not-drupal/not-core',
          ],
          (object) [
            'not-name-key' => 'vendor/package',
          ]
        ]));
        self::assertNotEmpty($handler->applyToItems([
          (object) [
            'name' => 'not-drupal/core',
          ],
        ]));
    }

    public function testWithLogger()
    {
        $config = Config::createFromComposerData((object) [
          'extra' => (object) [
            'violinist' => (object) [
              'allow_list' => ['drupal/*']
            ],
          ],
        ]);
        $items = [
          (object) [
            'name' => 'package1',
          ],
          (object) [
            'name' => 'package2',
          ],
          (object) [
            'name' => 'package3',
          ],
          (object) [
            'name' => 'drupal/core',
          ],
        ];
        $handler = AllowListHandler::createFromConfig($config);
        $logger = new ArrayLogger();
        $handler->setLogger($logger);
        $handler->applyToItems($items);
        $log_items = $logger->get();
        foreach ($items as $item) {
            if ($item->name === 'drupal/core') {
                continue;
            }
            $found = false;
            foreach ($log_items as $log_item) {
                if (strpos($log_item["message"], $item->name) === false) {
                    continue;
                }
                $found = true;
            }
            self::assertTrue($found);
        }
    }

    public function getEmptyVariations()
    {
        return [
            [
                'empty1',
            ],
            [
                'empty2',
            ],
            [
                'empty3',
            ],
            [
                'empty4',
            ],

        ];
    }
}
