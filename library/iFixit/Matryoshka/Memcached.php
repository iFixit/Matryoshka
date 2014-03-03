<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Memcached extends Backend {
   const FLAGS = MEMCACHE_COMPRESSED;

   private $memcached;

   // TODO: Change to Memcached.
   public function __construct(\Memcache $memcache) {
      $this->memcached = $memcache;
   }

   public function set($key, $value, $expiration = 0) {
      return $this->memcached->set($key, $value, self::FLAGS, $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      return $this->memcached->add($key, $value, self::FLAGS, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      $result = $this->memcached->increment($key, $amount);

      if ($result !== false) {
         return $result;
      }

      if ($this->memcached->set($key, $amount, $expiration) !== false) {
         return $amount;
      } else {
         return false;
      }
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      // TODO: Memcached doesn't support decrementing under 0 so there isn't
      // much we can do for the missing case.
      return $this->memcached->decrement($key, $amount);
   }

   public function get($key) {
      $value = $this->memcached->get($key);

      return $value === false ? self::MISS : $value;
   }

   public function delete($key) {
      return $this->memcached->delete($key);
   }
}
