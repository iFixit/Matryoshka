<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Memcache extends Backend {
   const FLAGS = MEMCACHE_COMPRESSED;
   const MAX_KEY_LENGTH = 255;

   private $memcache;

   public static function isAvailable() {
      return class_exists('Memcache', false);
   }

   /**
    * Factory method. This forces Memcache to always be wrapped in a
    * KeyShorten to fix keys that are too long and would otherwise get
    * truncated.
    */
   public static function create(\Memcache $memcache) {
      return new KeyShorten(new Memcache($memcache), self::MAX_KEY_LENGTH);
   }

   private function __construct(\Memcache $memcache) {
      $this->memcache = $memcache;
   }

   public function set($key, $value, $expiration = 0) {
      return $this->memcache->set($key, $value, self::FLAGS, $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      return $this->memcache->add($key, $value, self::FLAGS, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      // Memcache doesn't support negative amounts for decrement or increment
      // so send it to decrement to handle it.
      if ($amount < 0) {
         return $this->decrement($key, -$amount, $expiration);
      }

      $result = $this->memcache->increment($key, $amount);

      // The docs say that false is returned if the key doesn't exist but some
      // clients return 0.
      if ($result !== false && $result !== 0) {
         return $result;
      }

      if ($this->memcache->set($key, $amount, 0, $expiration) !==
       false) {
         return $amount;
      } else {
         return false;
      }
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      // Memcache doesn't support negative amounts for decrement or increment
      // so send it to increment to handle it.
      if ($amount < 0) {
         return $this->increment($key, -$amount, $expiration);
      }

      // TODO: Memcache doesn't support decrementing under 0 so there isn't
      // much we can do for the missing case.
      return $this->memcache->decrement($key, $amount);
   }

   public function get($key) {
      $value = $this->memcache->get($key);

      return $value === false ? self::MISS : $value;
   }

   public function getMultiple(array $keys) {
      // Default to an empty array in case no keys were found.
      $hits = $this->memcache->get(array_keys($keys)) ?: [];

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
      return $this->memcache->delete($key);
   }
}
