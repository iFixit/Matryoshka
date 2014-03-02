<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

/**
 * Base class for cache backends.
 */
abstract class Backend {
   const MISS = null;

   // TODO: Set and delete should return true/false for success/failure.
   public abstract function set($key, $value, $expiration = 0);
   public abstract function get($key);
   public abstract function delete($key);

   public function getAndSet($key, callable $callback, $expiration = 0) {
      $value = $this->get($key);

      if ($value === self::MISS) {
         $value = $callback();
         $this->set($key, $value, $expiration);
      }

      return $value;
   }
}
