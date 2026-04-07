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
         $key = $this->getScopeKey();

         if ($reset) {
            // Intentional overwrite (used by deleteScope())
            $scopeValue = $this->generateScopeValue();
            $this->backend->set($key, $scopeValue);
         } else {
            $scopeValue = $this->backend->get($key);
            if ($scopeValue === self::MISS) {
               if (!$generateOnMiss) {
                  return self::MISS;
               }
               $scopeValue = $this->generateScopeValue();
               // Use add() for first-writer-wins atomicity.
               // If another process already wrote the key, use their value.
               if (!$this->backend->add($key, $scopeValue)) {
                  $scopeValue = $this->backend->get($key) ?? $scopeValue;
               }
            }
         }

         $this->scopePrefix = "{$scopeValue}-";
      }

      return $this->scopePrefix;
   }

   private function generateScopeValue(): string {
      return substr(md5(microtime() . $this->scopeName), 0, 16);
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
