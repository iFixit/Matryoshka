<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class APCu extends Backend {
   public static function isAvailable() {
      return function_exists('apcu_enabled') && apcu_enabled();
   }

   public function set($key, $value, $expiration = 0) {
      return apcu_store($key, $value, $expiration);
   }

   public function setMultiple(array $values, $expiration = 0) {
      return apcu_store($values, null, $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      return apcu_add($key, $value, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      $value = apcu_inc($key, $amount, $success);

      // Call set() if the key doesn't exist.
      if ($success) {
         return $value;
      } else if ($this->set($key, $amount, $expiration) !== false) {
         return $amount;
      } else {
         return false;
      }
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      return $this->increment($key, -$amount, $expiration);
   }

   public function get($key) {
      $value = apcu_fetch($key, $success);

      return $success ? $value : self::MISS;
   }

   public function getMultiple(array $keys) {
      if (empty($keys)) {
         return [[],[]];
      }

      // Default to an empty array in case no keys were found.
      $hits = apcu_fetch(array_keys($keys)) ?: [];

      $found = [];
      $missed = [];
      foreach ($keys as $key => $id) {
         $value = array_key_exists($key, $hits) ? $hits[$key] : self::MISS;
         $found[$key] = $value;

         if ($value === self::MISS) {
            $missed[$key] = $id;
         }
      }

      return [$found, $missed];
   }

   public function delete($key) {
      return apcu_delete($key);
   }

   public function deleteMultiple(array $keys) {
      // apcu_delete returns an array of errors if you provide an array of keys
      // so the only successful case is no errors (empty array).
      $ret = apcu_delete($keys);
      return empty($ret);
   }
}
