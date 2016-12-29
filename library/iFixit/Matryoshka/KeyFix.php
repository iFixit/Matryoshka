<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

/**
 * Shortens long keys and fixes keys with characters that normally aren't
 * allowed by hashing the key.
 */
class KeyFix extends KeyChange {
   const MD5_STRLEN = 32;

   private $maxLength;
   private $invalidChars;

   public function __construct(Backend $backend, $maxLength,
    $invalidChars = " \n") {
      if ($maxLength < self::MD5_STRLEN) {
         throw new \InvalidArgumentException(
          'Max length must be larger than ' . self::MD5_STRLEN);
      }

      if (!is_string($invalidChars) || $invalidChars === '') {
         throw new \InvalidArgumentException(
          'Bad character mask: ' . $invalidChars);
      }

      parent::__construct($backend);

      $this->maxLength = $maxLength;
      $this->invalidChars = $invalidChars;
   }

   public function changeKey($key) {
      if ($this->safeKey($key)) {
         return $key;
      }

      return md5($key);
   }

   public function changeKeys(array $keys) {
      foreach ($keys as $key => $value) {
         if (!$this->safeKey($key)) {
            $keys[md5($key)] = $value;
            unset($keys[$key]);
         }
      }

      return $keys;
   }

   /**
    * Key is safe if it isn't too long, and if it doesn't contain any bad
    * characters.
    */
   private function safeKey($key) {
      return strlen($key) <= $this->maxLength &&
       strpbrk($key, $this->invalidChars) === false;
   }
}
