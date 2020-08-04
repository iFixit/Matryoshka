<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class McRouter extends Memcached {
   /**
    * Override the Memcahed factory method. Fail hard if binary protocol is
    * set on the \Memcached backend. Mcrouter only supports ASCII protocol.
    */
   public static function create(\Memcached $memcached) {
      if ($memcached->getOption(\Memcached::OPT_BINARY_PROTOCOL)) {
         throw new \InvalidArgumentException("Binary Protocol is not supported");
      }

      return new KeyFix(new self($memcached),
       self::MAX_KEY_LENGTH, Memcached::INVALID_CHARS_REGEX);
   }

   /**
    * Override the `set` method. Use `add` which is synchronous to detect
    * `set` over-top of existing keys. Delete and reset them to
    * enforce consistency.
    */
   public function set($key, $value, $expiration = 0) {
      $addReturn = $this->memcached->add($key, $value, $expiration);
      if ($addReturn) {
         return $addReturn;
      }
      $this->memcached->delete($key);
      return $this->memcached->set($key, $value, $expiration);
   }

   /**
    * Override the `setMultiple` method. Use our add->delete->set logic
    * from `set` to serially set keys.
    */
   public function setMultiple(array $values, $expiration = 0) {
      $success = true;
      foreach ($values as $key => $value) {
         $success = $success && $this->set($key, $value, $expiration);
      }
      return $success;
   }

   /**
    * override the `increment` method. Increment is synchronous in Mcrouter,
    * so if it fails, reset to the initial value for consistency.
    */
   public function increment($key, $amount = 1, $expiration = 0) {
      // Memcache doesn't support negative amounts for decrement or increment
      // so send it to decrement to handle it.
      if ($amount < 0) {
         return $this->decrement($key, -$amount);
      }

      $incrReturn = $this->memcached->increment($key, $amount);

      if (is_int($incrReturn)) {
         return $incrReturn;
      }

      $this->set($key, $amount, $expiration);
      return $amount;
   }

   /**
    * override the `decrement` method. Decrement is also synchronous in
    * Mcrouter, so if it fails, reset to the initial value for consistency.
    */
   public function decrement($key, $amount = 1, $expiration = 0) {
      // Memcached doesn't support negative amounts for decrement or increment
      // so send it to increment to handle it.
      if ($amount < 0) {
         return $this->increment($key, -$amount);
      }

      $decrReturn = $this->memcached->decrement($key, $amount);

      if (is_int($decrReturn)) {
         return $decrReturn;
      }

      $this->set($key, 0, $expiration);
      return 0;
   }
}
