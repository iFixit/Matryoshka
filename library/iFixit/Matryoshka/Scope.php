<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Scope extends KeyChange {
   private $backend;
   private $scopeName;
   private $scopePrefix;

   public function __construct(Backend $backend, $scopeName) {
      parent::__construct($backend);

      $this->scopeName = $scopeName;
      $this->backend = $backend;
   }

   public function changeKey($key) {
      $prefix = $this->getScopePrefix();

      return "{$prefix}$key";
   }

   public function getScopePrefix($reset = false) {
      if ($this->scopePrefix === null || $reset) {
         $this->scopePrefix = $this->backend->getAndSet($this->getScopeKey(),
          function() {
            return substr(md5(microtime()), 0, 4);
         }, 0, $reset);
      }

      return $this->scopePrefix;
   }

   public function getScopeName() {
      return $this->scopeName;
   }

   /**
    * Deletes the scope which effectively invalidates all cache entries under
    * this scope.
    *
    * @return true on success.
    */
   public function deleteScope() {
      // Delete the scope by setting a new value for it.
      $prefix = $this->getScopePrefix($reset = true);

      return $prefix !== self::MISS;
   }

   private function getScopeKey() {
      return "scope-{$this->scopeName}";
   }
}
