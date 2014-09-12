<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Scope extends Prefix {
   private $backend;
   private $scopeName;
   private $scopePrefix;

   public function __construct(Backend $backend, $scopeName) {
      // The prefix we pass along to the Prefix() constructor is never used, so 
      // it doesn't matter.
      parent::__construct($backend, /* $prefix = */ null);

      $this->scopeName = $scopeName;
      $this->backend = $backend;
   }

   public function getPrefix() {
      return $this->scopePrefix ?: $this->getScopePrefix();
   }

   public function getScopePrefix($reset = false) {
      if ($this->scopePrefix === null || $reset) {
         $scopeValue = $this->backend->getAndSet($this->getScopeKey(),
          function() {
            return substr(md5(microtime() . $this->scopeName), 0, 8);
         }, 0, $reset);

         $this->scopePrefix = "{$scopeValue}-";
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
