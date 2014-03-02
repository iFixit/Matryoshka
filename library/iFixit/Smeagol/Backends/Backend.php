<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

/**
 * Base class for cache backends.
 */
abstract class Backend {
   const MISS = null;

   public abstract function set($key, $value, $expiration = 0);
   public abstract function get($key);
   public abstract function delete($key);

   public function getAndSet($key, callable $callback, $expiration = 0) {
      $value = $this->get($key);

      if ($value === null) {
         $value = $callback();
         $this->set($key, $value, $expiration);
      }

      return $value;
   }
}
