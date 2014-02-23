<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

class Memcached extends Smeagol\Backends\Backend {
   private $memcached;

   // TODO: Change to Memcached.
   public function __construct(\Memcache $memcache) {
      $this->memcached = $memcache;
   }

   public function set($key, $value) {
      $this->memcached->set($key, $value);
   }

   public function get($key) {
      $value = $this->memcached->get($key);

      return $value === false ? $this->miss() : $value;
   }

   public function delete($key) {
      return $this->memcached->delete($key);
   }
}
