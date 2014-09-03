<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Simple in-memory PHP array to cache objects for this process.
 *
 * Note: The expiration time is ignored completely.
 */
class Ephemeral extends Backend {
   protected $cache;

   public function __construct() {
      $this->cache = [];
   }

   public function set($key, $value, $expiration = 0) {
      $this->cache[$key] = $value;

      return true;
   }

   public function setMultiple(array $values, $expiration = 0) {
      foreach ($values as $key => $value) {
         $this->cache[$key] = $value;
      }

      return true;
   }

   public function add($key, $value, $expiration = 0) {
      if (!array_key_exists($key, $this->cache)) {
         $this->cache[$key] = $value;
         return true;
      } else {
         return false;
      }
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      if (array_key_exists($key, $this->cache) &&
       is_numeric($this->cache[$key])) {
         $this->cache[$key] += $amount;
         return $this->cache[$key];
      } else {
         $this->cache[$key] = (int)$amount;
         return $amount;
      }
   }

   public function get($key) {
      if (array_key_exists($key, $this->cache)) {
         return $this->cache[$key];
      } else {
         return self::MISS;
      }
   }

   public function getMultiple(array $keys) {
      $found = [];
      $missing = [];

      foreach ($keys as $key => $id) {
         if (array_key_exists($key, $this->cache)) {
            $found[$key] = $this->cache[$key];
         } else {
            $found[$key] = self::MISS;
            $missing[$key] = $id;
         }
      }

      return [$found, $missing];
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
