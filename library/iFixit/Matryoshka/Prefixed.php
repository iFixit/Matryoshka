<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Prefixed extends KeyChanger {
   private $prefix;

   public function __construct(Backend $backend, $prefix) {
      parent::__construct($backend);

      $this->prefix = $prefix;
   }

   public function changeKey($key) {
      return $this->prefix . $key;
   }
}
