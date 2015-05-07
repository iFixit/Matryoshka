<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Memcached extends Backend {
   const MAX_KEY_LENGTH = 250;
   private $memcached;

   public static function isAvailable() {
      return class_exists('\Memcached', false);
   }

   /**
    * Factory method. This forces Memcached to always be wrapped in a
    * KeyShorten to fix keys that are too long and would otherwise get
    * truncated.
    */
   public static function create(\Memcached $memcached) {
      return new KeyShorten(new self($memcached), self::MAX_KEY_LENGTH);
   }

   private function __construct(\Memcached $memcached) {
      $this->memcached = $memcached;
   }

   public function set($key, $value, $expiration = 0) {
      return $this->memcached->set($key, $value, $expiration);
   }

   public function setMultiple(array $values, $expiration = 0) {
      return $this->memcached->setMulti($values, $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      return $this->memcached->add($key, $value, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      // Memcache doesn't support negative amounts for decrement or increment
      // so send it to decrement to handle it.
      if ($amount < 0) {
         return $this->decrement($key, -$amount, $expiration);
      }

      return $this->memcached->increment($key, $amount, /* initial */ $amount,
       $expiration);
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      // Memcached doesn't support negative amounts for decrement or increment
      // so send it to increment to handle it.
      if ($amount < 0) {
         return $this->increment($key, -$amount, $expiration);
      }

      return $this->memcached->decrement($key, $amount, /* initial */ $amount,
       $expiration);
   }

   public function get($key) {
      $value = $this->memcached->get($key);

      return $value === false ? self::MISS : $value;
   }

   public function getMultiple(array $keys) {
      if (empty($keys)) {
         return [[],[]];
      }

      /**
       * \Memcached::GET_PRESERVE_ORDER makes it so all keys are returned in
       * the order that they were requested with null indicating a miss which
       * is exactly what is needed for the found array.
       */
      $cas_tokens = null;
      $found = $this->memcached->getMulti(array_keys($keys), $cas_tokens,
       \Memcached::GET_PRESERVE_ORDER);

      $missed = [];
      foreach ($keys as $key => $id) {
         if ($found[$key] === self::MISS) {
            $missed[$key] = $id;
         }
      }

      return [$found, $missed];
   }

   public function delete($key) {
      return $this->memcached->delete($key);
   }
}
