<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

/**
 * Simple in-memory PHP array to cache objects for this process.
 */
class MemoryArray extends Backend {
   private $cache;

   public function __construct() {
      $this->cache = [];
   }

   public function set($key, $value, $expiration = 0) {
      // TODO: This doesn't use the expiration time at all.
      $this->cache[$key] = $value;
   }

   public function get($key) {
      if (array_key_exists($key, $this->cache)) {
         return $this->cache[$key];
      } else {
         return self::MISS;
      }
   }

   public function delete($key) {
      unset($this->cache[$key]);
   }
}
