<?php

namespace iFixit\Matryoshka;

use iFixit\Matryoshka;

class MultiScoped extends KeyChanger {
   private $scopes;

   public function __construct(Backend $backend, array $scopes = []) {
      parent::__construct($backend);

      foreach ($scopes as $scope) {
         if (!($scope instanceof Scoped)) {
            $type = is_object($scope) ? get_class($scope) : gettype($scope);
            throw new \InvalidArgumentException(
             "Not an instance of Scoped: $type");
         }
      }

      $this->scopes = $scopes;
      $this->sortScopes();
   }

   public function addScope(Scoped $scoped) {
      $this->scopes[] = $scoped;
      $this->sortScopes();

      return $this;
   }

   public function changeKey($key) {
      $scopedKey = '';

      foreach ($this->scopes as $scope) {
         $scopedKey .= $scope->getScopePrefix() . '-';
      }

      return "{$scopedKey}{$key}";
   }

   private function sortScopes() {
      usort($this->scopes, function($a, $b) {
         return strcmp($a->getScopeName(), $b->getScopeName());
      });
   }
}
