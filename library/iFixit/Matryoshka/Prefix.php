<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Prefix extends KeyChange {
   private $prefix;

   public function __construct(Backend $backend, $prefix) {
      parent::__construct($backend);

      $this->prefix = $prefix;
   }

   public function changeKey($key) {
      return $this->getPrefix() . $key;
   }

   public function changeKeys(array $keys) {
      $prefix = $this->getPrefix();
      $changedKeys = [];

      foreach ($keys as $key => $value) {
         $changedKeys["{$prefix}{$key}"] = $value;
      }

      return $changedKeys;
   }

   public function getPrefix() {
      return $this->prefix;
   }
}
