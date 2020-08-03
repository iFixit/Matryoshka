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

   /** @var string */
   private $invalidRegex;

   public function __construct(Backend $backend, $maxLength,
    $invalidRegex) {
      if ($maxLength < self::MD5_STRLEN) {
         throw new \InvalidArgumentException(
          'Max length must be larger than ' . self::MD5_STRLEN);
      }

      $isRegex = @preg_match($invalidRegex, '') !== false;

      if (!is_string($invalidRegex) || !$isRegex) {
         throw new \InvalidArgumentException(
          'Not a valid regex: ' . $invalidRegex);
      }

      parent::__construct($backend);

      $this->maxLength = $maxLength;
      $this->invalidRegex = $invalidRegex;
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
    *
    * @param array-key $key
    */
   private function safeKey($key) {
      return strlen($key) <= $this->maxLength &&
      preg_match($this->invalidRegex, $key) === 0;
   }
}
