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

   public function getScopePrefix(bool $reset = false, bool $generateOnMiss = true) {
      if ($this->scopePrefix === null || $reset) {
         $scopeValue = $reset ? self::MISS : $this->backend->get($this->getScopeKey());
         if ($scopeValue === self::MISS) {
            if ($generateOnMiss) {
               $scopeValue = substr(md5(microtime() . $this->scopeName), 0, 16);
               $this->scopePrefix = "{$scopeValue}-";
               $this->backend->set($this->getScopeKey(), $scopeValue);
            } else {
               return self::MISS;
            }
         } else {
            $this->scopePrefix = "{$scopeValue}-";
         }
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
      // Delete the scope by setting a new value for it.
      $prefix = $this->getScopePrefix($reset = true);

      return $prefix !== self::MISS;
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
