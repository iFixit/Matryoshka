<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class PrefixTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\Prefix(new Matryoshka\Ephemeral(), 'prefix');
   }

   public function testPrefix() {
      $memoryCache = new Matryoshka\Ephemeral();
      $prefix = 'prefix';
      $prefixedCache = new Matryoshka\Prefix($memoryCache,
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

   public function testAbsoluteKey() {
      $memoryCache = new Matryoshka\Ephemeral();
      $prefix = 'prefix';
      $prefixedCache = new Matryoshka\Prefix($memoryCache, $prefix);
      [$key] = $this->getRandomKeyValue();

      $this->assertEquals($prefixedCache->getPrefix() . $key, $prefixedCache->getAbsoluteKey($key));
   }
}
