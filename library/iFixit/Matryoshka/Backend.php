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
    * Increments the value associated with the given key by the given amount.
    * If the key does not exist or the existing value is not numeric, it is set
    * to the given value instead. Some backends have different rules for valid
    * values and ranges.
    *
    * Also see: decrement
    *
    * @return the updated value, or false on failure
    */
   public abstract function increment($key, $amount = 1, $expiration = 0);

   /**
    * Retrieves the value associated with the key.
    *
    * @return the value or null on failure or if it is not found
    */
   public abstract function get($key);

   /**
    * Retrieves multiple keys/values.
    *
    * Note: It is recommended to use getAndSetMultiple instead.
    *
    * @param $keys An array of [key => id] where id is whatever the caller
    *              wants to use to identify the missed values.
    *
    * @return An array of found and missed values e.g.
    *         [
    *            [key => value],
    *            [key => id]
    *         ]
    *         The first array contains all of the provided keys in the same
    *         order. Any values not in the cache are returned as null in the
    *         found array and have the same id in the missing array.
    */
   public abstract function getMultiple(array $keys);

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

   /**
    * Wrapper around getMultiple that uses the provided callback to retrieve
    * and populate the cache for any misses.
    *
    * @param callback
    *           @param missing Array of [key => id] that the caller needs to
    *                          generate the values for.
    *           @return Array of [key => value] for the found values. The
    *                   order does not matter. Additional key/values in the
    *                   array that are not in the missing array are ignored.
    * @return Array of [key => value] in the same order as the requested keys.
    *         This does not include values not returned by the callback.
    */
   public function getAndSetMultiple(array $keys, callable $callback,
    $expiration = 0) {
      list($found, $missing) = $this->getMultiple($keys);

      if (empty($missing)) {
         return $found;
      }

      $values = $callback($missing);

      foreach ($values as $key => $value) {
         // Ignore keys that weren't originally requested or are misses.
         // TODO: setMultiple?
         if (array_key_exists($key, $keys) && $value !== self::MISS) {
            $found[$key] = $value;
            $this->set($key, $value, $expiration);
         }
      }

      // Remove misses.
      foreach ($found as $key => $value) {
         if ($value === self::MISS) {
            unset($found[$key]);
         }
      }

      return $found;
   }

   /**
    * Same as increment but subtracts the amount rather than adding it. Some
    * backends have different rules for valid values and ranges.
    *
    * @return the updated value, or false on failure
    */
   public function decrement($key, $amount = 1, $expiration = 0) {
      return $this->increment($key, -$amount, $expiration);
   }
}
