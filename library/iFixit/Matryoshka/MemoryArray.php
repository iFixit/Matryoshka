<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

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

      return true;
   }

   public function add($key, $value, $expiration = 0) {
      // TODO: This doesn't use the expiration time at all.
      if (!array_key_exists($key, $this->cache)) {
         $this->cache[$key] = $value;
         return true;
      } else {
         return false;
      }
   }

   public function get($key) {
      if (array_key_exists($key, $this->cache)) {
         return $this->cache[$key];
      } else {
         return self::MISS;
      }
   }

   public function delete($key) {
      if (array_key_exists($key, $this->cache)) {
         unset($this->cache[$key]);
         return true;
      } else {
         return false;
      }
   }
}
