<?php

namespace iFixit\Smeagol\Backends;

use iFixit\Smeagol;

class Prefixed extends Smeagol\Backends\KeyChanger {
   private $prefix;

   public function __construct(Smeagol\Backends\Backend $backend, $prefix) {
      parent::__construct($backend);

      $this->prefix = $prefix;
   }

   public function changeKey($key) {
      return "{$this->prefix}$key";
   }
}
