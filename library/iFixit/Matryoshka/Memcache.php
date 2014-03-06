<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Memcache extends Backend {
   const FLAGS = MEMCACHE_COMPRESSED;

   private $memcache;

   public function __construct(\Memcache $memcache) {
      $this->memcache = $memcache;
   }

   public function set($key, $value, $expiration = 0) {
      return $this->memcache->set($key, $value, self::FLAGS, $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      return $this->memcache->add($key, $value, self::FLAGS, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      $result = $this->memcache->increment($key, $amount);

      if ($result !== false) {
         return $result;
      }

      if ($this->memcache->set($key, $amount, self::FLAGS, $expiration) !==
       false) {
         return $amount;
      } else {
         return false;
      }
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      // TODO: Memcache doesn't support decrementing under 0 so there isn't
      // much we can do for the missing case.
      return $this->memcache->decrement($key, $amount);
   }

   public function get($key) {
      $value = $this->memcache->get($key);

      return $value === false ? self::MISS : $value;
   }

   public function delete($key) {
      return $this->memcache->delete($key);
   }
}
