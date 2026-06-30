<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class Scope extends Prefix {
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

   public function getScopePrefix(bool $generateOnMiss = true) {
      if ($this->scopePrefix === null) {
         $scopeValue = $this->backend->get($this->getScopeKey());
         if ($scopeValue === self::MISS) {
            if ($generateOnMiss) {
               $scopeValue = substr(md5(microtime() . $this->scopeName), 0, 16);
               $this->backend->set($this->getScopeKey(), $scopeValue);
            } else {
               return self::MISS;
            }
         }
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
    */
   public function deleteScope(): bool {
      // Explicitly delete the scope key from the backend to ensure that it is
      // removed.
      $this->backend->delete($this->getScopeKey());
      $this->scopePrefix = null;

      return true;
   }

   private function getScopeKey() {
      return "scope-{$this->scopeName}";
   }

   public function get($key) {
      // If the scope prefix doesn't exist, all keys in this scope are a miss.
      if (!$this->getScopePrefix(generateOnMiss: false)) {
         return self::MISS;
      }
      return parent::get($key);
   }

   public function getMultiple(array $keys) {
      // If the scope prefix doesn't exist, all keys in this scope are a miss.
      if (!$this->getScopePrefix(generateOnMiss: false)) {
         return [array_fill_keys(array_keys($keys), self::MISS), $keys];
      }
      return parent::getMultiple($keys);
   }
}
