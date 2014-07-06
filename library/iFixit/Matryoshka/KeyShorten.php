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
      parent::__construct($backend);

      $this->maxLength = $maxLength;
   }

   public function changeKey($key) {
      if (strlen($key) <= $this->maxLength) {
         return $key;
      }

      return substr($key, 0, $this->maxLength - self::MD5_STRLEN) . md5($key);
   }
}
