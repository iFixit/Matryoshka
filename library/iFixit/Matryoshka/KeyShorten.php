<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Shortens long keys by hashing the entire key and appending it to a substring
 * of the original key.
 */
class KeyShorten extends KeyChange {
   const MD5_STRLEN = 32;

   private $maxLength;

   public function __construct(Backend $backend, $maxLength) {
      if ($maxLength < self::MD5_STRLEN) {
         throw new \InvalidArgumentException(
          'Max length must be larger than ' . self::MD5_STRLEN);
      }

      parent::__construct($backend);

      $this->maxLength = $maxLength;
   }

   public function changeKey($key) {
      if (strlen($key) <= $this->maxLength) {
         return $key;
      }

      return substr($key, 0, $this->maxLength - self::MD5_STRLEN) . md5($key);
   }

   public function changeKeys(array $keys) {
      $changedKeys = [];

      foreach ($keys as $key => $value) {
         if (strlen($key) <= $this->maxLength) {
            $newKey = $key;
         } else {
            $newKey = substr($key, 0, $this->maxLength - self::MD5_STRLEN) . md5($key);
         }

         $changedKeys[$newKey] = $value;
      }

      return $changedKeys;
   }
}
