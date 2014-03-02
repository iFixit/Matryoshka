<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

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

   public function get($key) {
      $value = $this->memcached->get($key);

      return $value === false ? self::MISS : $value;
   }

   public function delete($key) {
      return $this->memcached->delete($key);
   }
}
