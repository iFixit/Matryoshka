<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Base class for cache backends.
 */
abstract class Backend {
   const MISS = null;

   /**
    * Associates the value with the key in the cache. The value will expire
    * after the specified expiration time in seconds.
    *
    * @return true on success, false on failure
    */
   public abstract function set($key, $value, $expiration = 0);

   /**
    * Same as set except it does nothing if the key already exists.
    *
    * @return true on success, false if the key exists or the operation fails
    */
   public abstract function add($key, $value, $expiration = 0);

   /**
    * Retrieves the value associated with the key.
    *
    * @return the value or null on failure or if it is not found
    */
   public abstract function get($key);

   /**
    * Deletes the cache entry with the given key.
    *
    * @return true on success, false on failure
    */
   public abstract function delete($key);

   /**
    * Wrapper around get and set that uses the provided callback to retrieve
    * and populate the cache if the key is not found in the cache.
    *
    * @return the value
    */
   public function getAndSet($key, callable $callback, $expiration = 0) {
      $value = $this->get($key);

      if ($value === self::MISS) {
         $value = $callback();
         $this->set($key, $value, $expiration);
      }

      return $value;
   }
}
