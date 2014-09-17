<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class ScopeTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\Scope(new Matryoshka\Ephemeral(), 'scope');
   }

   public function testScope() {
      $memoryCache = new Matryoshka\Ephemeral();
      $scope = 'scope';
      $scopedCache = new Matryoshka\Scope($memoryCache,
       $scope);
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      $this->assertNull($scopedCache->get($key1));

      $this->assertTrue($scopedCache->set($key1, $value1));

      $this->assertSame($value1, $scopedCache->get($key1));

      $this->assertTrue($scopedCache->delete($key1));

      $this->assertNull($scopedCache->get($key1));

      $this->assertTrue($scopedCache->set($key1, $value1));
      $this->assertTrue($scopedCache->set($key2, $value2));

      $this->assertSame($value1, $scopedCache->get($key1));
      $this->assertSame($value2, $scopedCache->get($key2));

      $this->assertTrue($scopedCache->deleteScope());

      $this->assertNull($scopedCache->get($key1));
      $this->assertNull($scopedCache->get($key2));

      $this->assertTrue($scopedCache->set($key1, $value1));

      $this->assertSame($value1, $scopedCache->get($key1));
   }
}
