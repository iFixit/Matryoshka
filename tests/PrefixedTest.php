<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class PrefixedTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\Prefixed(new Matryoshka\MemoryArray(), 'prefix');
   }

   public function testPrefixed() {
      $memoryCache = new Matryoshka\MemoryArray();
      $prefix = 'prefix';
      $prefixedCache = new Matryoshka\Prefixed($memoryCache,
       $prefix);
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($prefixedCache->get($key));

      $this->assertTrue($prefixedCache->set($key, $value));

      $this->assertSame($value, $prefixedCache->get($key));
      $this->assertSame($value, $memoryCache->get("{$prefix}{$key}"));
      $this->assertNull($memoryCache->get($key));

      $this->assertTrue($prefixedCache->delete($key));

      $this->assertNull($prefixedCache->get($key));
      $this->assertNull($memoryCache->get("{$prefix}{$key}"));
      $this->assertNull($memoryCache->get($key));
   }
}
