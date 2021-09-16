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

   /**
    * TODO: the other backends' setMultiple() returns a bool, we should fix the way we're
    * handling this for consistency. See: https://github.com/iFixit/Matryoshka/issues/30
    *
    * @return array Returns array of keys that had errors
    */
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

      /**
       * @psalm-suppress InvalidArgument
       *
       * Default to an empty array in case no keys were found.
       */
      $hits = apcu_fetch(array_keys($keys)) ?: [];

      $found = [];
      $missed = [];
      foreach ($keys as $key => $id) {
         $value = array_key_exists($key, $hits) ? $hits[$key] : self::MISS;
         // Abstract class function docs say $found[] should contain all keys
         // with null values for the misses, so we store the result even
         // when it's a miss.
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
      // The docs leave out the fact that apcu_delete() can take an array of keys,
      // when you provide an array of keys, it provides an array of errors (if any)
      // so the only successful case is no errors (empty array).
      $ret = apcu_delete($keys);
      /**
       * In the docs, the apcu_delete returns an array if passed one.
       * Ref: https://www.php.net/manual/en/function.apcu-delete.php
       * Psalm gets this wrong.
       * @psalm-suppress InvalidArgument
       */
      return empty($ret);
   }

   public function getAbsoluteKey($key) {
      return $key;
   }
}
