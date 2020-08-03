<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Memcached extends Backend {
   const MAX_KEY_LENGTH = 250;

   /**
    * From: https://github.com/memcached/memcached/blob/master/doc/protocol.txt
    * > Currently the length limit of a key is set at 250 characters
    * > the key must not include control characters or whitespace.
    *
    * See: https://www.oreilly.com/library/view/programming-php-3rd/9781449361068/ch04s08.html#character_classes-id2
    */
   const INVALID_CHARS_REGEX = '/[^[:graph:]]/';

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
      return new KeyFix(new self($memcached), self::MAX_KEY_LENGTH, self::INVALID_CHARS_REGEX);
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

   /**
    * @template T
    * @return T|self::MISS
    */
   public function get($key) {
      /** @var T|false */
      $value = $this->memcached->get($key);
      $resultCode = $this->memcached->getResultCode();

      if ($resultCode === \Memcached::RES_SUCCESS) {
         /** @var T */
         return $value;
      }

      if ($resultCode === \Memcached::RES_NOTFOUND) {
         return self::MISS;
      }

      $errorMessage = $this->memcached->getResultMessage();
      throw new \MemcachedException($errorMessage, $resultCode);
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
      $found = $this->memcached->getMulti(array_keys($keys),
       \Memcached::GET_PRESERVE_ORDER);

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
}
