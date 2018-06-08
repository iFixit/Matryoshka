<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Memcached extends Backend {
   const MAX_KEY_LENGTH = 250;

   // Is using instance of Memcache whose `getMulti` only accepts two args.
   protected static $getMultiHasTwoParams = null;

   protected $memcached;

   public static function isAvailable() {
      return class_exists('\Memcached', false);
   }

   /**
    * Factory method. This forces Memcached to always be wrapped in a
    * KeyShorten to fix keys that are too long and would otherwise get
    * truncated.
    */
   public static function create(\Memcached $memcached) {
      self::setGetMultiParams($memcached);

      return new KeyFix(new self($memcached), self::MAX_KEY_LENGTH);
   }

   protected function __construct(\Memcached $memcached) {
      $this->memcached = $memcached;
   }

   public function set($key, $value, $expiration = 0) {
      return $this->memcached->set($key, $value, $expiration);
   }

   public function setMultiple(array $values, $expiration = 0) {
      return $this->memcached->setMulti($values, $expiration);
   }

   public function add($key, $value, $expiration = 0) {
      return $this->memcached->add($key, $value, $expiration);
   }

   public function increment($key, $amount = 1, $expiration = 0) {
      // Memcache doesn't support negative amounts for decrement or increment
      // so send it to decrement to handle it.
      if ($amount < 0) {
         return $this->decrement($key, -$amount, $expiration);
      }

      return $this->memcached->increment($key, $amount, /* initial */ $amount,
       $expiration);
   }

   public function decrement($key, $amount = 1, $expiration = 0) {
      // Memcached doesn't support negative amounts for decrement or increment
      // so send it to increment to handle it.
      if ($amount < 0) {
         return $this->increment($key, -$amount, $expiration);
      }

      return $this->memcached->decrement($key, $amount, /* initial */ $amount,
       $expiration);
   }

   public function get($key) {
      $value = $this->memcached->get($key);

      return $value === false ? self::MISS : $value;
   }

   public function getMultiple(array $keys) {
      if (empty($keys)) {
         return [[],[]];
      }

      /**
       * \Memcached::GET_PRESERVE_ORDER makes it so all keys are returned in
       * the order that they were requested with null indicating a miss which
       * is exactly what is needed for the found array.
       */
      if (self::$getMultiHasTwoParams === true) {
         // The PHP7 version of Memcached (at the time of this writing) does not
         // accept a `cas_token` param.
         $found = $this->memcached->getMulti(array_keys($keys),
          \Memcached::GET_PRESERVE_ORDER);
      } else {
         $cas_tokens = null;
         $found = $this->memcached->getMulti(array_keys($keys), $cas_tokens,
          \Memcached::GET_PRESERVE_ORDER);
      }

      $missed = [];
      foreach ($keys as $key => $id) {
         if ($found[$key] === self::MISS) {
            $missed[$key] = $id;
         }
      }

      return [$found, $missed];
   }

   public function delete($key) {
      return $this->memcached->delete($key);
   }

   public function deleteMultiple(array $keys) {
      // Some environments (HHVM) don't implement deleteMulti so we need to
      // roll it ourselves.
      if (!method_exists($this->memcached, 'deleteMulti')) {
         $success = true;
         foreach ($keys as $key) {
            $success = $this->memcached->delete($key) && $success;
         }

         return $success;
      }

      $results = $this->memcached->deleteMulti($keys);

      foreach ($results as $key => $success) {
         if ($success !== true) {
            return false;
         }
      }

      return true;
   }

   /**
    * The PHP 7 version of Memcached has a different API. We can tell which
    * API to use by how many arguments the method takes.
    */
   protected static function setGetMultiParams(\Memcached $memcached): void {
      if (self::$getMultiHasTwoParams === null) {
         $getMulti = new \ReflectionMethod($memcached, 'getMulti');
         $numArgs = $getMulti->getNumberOfParameters();

         self::$getMultiHasTwoParams = $numArgs === 2;
      }
   }
}
