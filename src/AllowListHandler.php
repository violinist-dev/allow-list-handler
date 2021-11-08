<?php

namespace Violinist\AllowListHandler;

use Psr\Log\LoggerAwareTrait;
use Violinist\Config\Config;

class AllowListHandler
{
    use LoggerAwareTrait;

    /**
     * The actual list.
     *
     * @var array
     */
    private $list = [];

    public function __construct(array $list)
    {
        $this->list = $list;
    }

    public function applyToItems(array $items)
    {
        if (!is_array($this->list) || empty($this->list)) {
            return $items;
        }
        foreach ($items as $delta => $item) {
            if (empty($item->name)) {
                // Hm, not much to do here i guess.
                continue;
            }
            foreach ($this->list as $list_item) {
                if (fnmatch($list_item, $item->name)) {
                    continue 2;
                }
            }
            if ($this->logger) {
                $this->logger->info(sprintf('Removing %s because it has no match in the allow list', $item->name));
            }
            unset($items[$delta]);
        }
        return array_values($items);
    }

    public static function createFromConfig(Config $config) : AllowListHandler
    {
        $allow_list = [];
        try {
            $allow_list = $config->getAllowList();
            assert(is_array($allow_list));
        } catch (\Throwable $e) {
        }
        return self::createFromArray($allow_list);
    }

    public static function createFromArray(array $list) : AllowListHandler
    {
        return new self($list);
    }
}
