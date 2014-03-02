<?php

require_once __DIR__ . '/../library/iFixit/Smeagol.php';

use iFixit\Smeagol;
use iFixit\Smeagol\Backends;

Smeagol::autoload();

class SmeagolTest extends PHPUnit_Framework_TestCase {
   public function testMemoryArray() {
      $cache = new Backends\MemoryArray();
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($cache->get($key));

      $this->assertTrue($cache->set($key, $value));

      $this->assertSame($value, $cache->get($key));

      $this->assertTrue($cache->delete($key));

      $this->assertNull($cache->get($key));
   }

   public function testHierarchy() {
      $backends = [
         new Backends\MemoryArray(),
         new Backends\Memcached($this->getMemcached())
      ];
      $hierarchy = new Backends\Hierarchy($backends);
      $allBackends = array_merge($backends, [$hierarchy]);
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($hierarchy->get($key));

      $this->assertTrue($hierarchy->set($key, $value));

      foreach ($allBackends as $backend) {
         $this->assertSame($value, $backend->get($key));
      }

      $this->assertTrue($backends[0]->delete($key));

      $this->assertSame($value, $hierarchy->get($key));
      $this->assertSame($value, $backends[0]->get($key));

      $this->assertTrue($hierarchy->delete($key));

      foreach ($allBackends as $backend) {
         $this->assertNull($backend->get($key));
      }

      list($key, $value) = $this->getRandomKeyValue();
      $this->assertTrue($hierarchy->set($key, $value));

      $this->assertTrue($backends[1]->delete($key));
      $this->assertSame($value, $hierarchy->get($key));
   }

   public function testPrefixed() {
      $memoryCache = new Backends\MemoryArray();
      $prefix = 'prefix';
      $prefixedCache = new Backends\Prefixed($memoryCache,
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

   public function testScoped() {
      $memoryCache = new Backends\MemoryArray();
      $scope = 'scope';
      $scopedCache = new Backends\Scoped($memoryCache,
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

   public function testMemcached() {
      $cache = new Backends\Memcached($this->getMemcached());
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($cache->get($key));

      $this->assertTrue($cache->set($key, $value));

      $this->assertSame($value, $cache->get($key));

      $this->assertTrue($cache->delete($key));

      $this->assertNull($cache->get($key));

      list($key, $value) = $this->getRandomKeyValue();
      $this->assertTrue($cache->set($key, $value, 1));
      // Wait for it to expire.
      sleep(2);

      $this->assertNull($cache->get($key));
   }

   public function testEnabled() {
      $cache = new Backends\Enabled(new Backends\MemoryArray());
      list($key, $value) = $this->getRandomKeyValue();

      $cache->setsEnabled = false;
      $this->assertFalse($cache->set($key, $value));
      $this->assertNull($cache->get($key));
      $cache->setsEnabled = true;

      $this->assertTrue($cache->set($key, $value));
      $this->assertSame($value, $cache->get($key));

      $cache->getsEnabled = false;
      $this->assertNull($cache->get($key));
      $cache->getsEnabled = true;

      $cache->deletesEnabled = false;
      $this->assertFalse($cache->delete($key));
      $this->assertSame($value, $cache->get($key));
      $cache->deletesEnabled = true;
   }

   public function testgetAndSet() {
      $cache = new Backends\MemoryArray();
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($cache->get($key));

      $hit = false;
      $callback = function() use ($value, &$hit) {
         $hit = true;
         return $value;
      };

      $getAndSetValue = $cache->getAndSet($key, $callback);

      $this->assertTrue($hit);
      $this->assertSame($value, $getAndSetValue);
      $this->assertSame($value, $cache->get($key));

      $hit = false;
      $getAndSetValue = $cache->getAndSet($key, $callback);

      $this->assertFalse($hit);
      $this->assertSame($value, $getAndSetValue);
      $this->assertSame($value, $cache->get($key));
   }

   private function getRandomKeyValue() {
      return [
         'key-' . microtime(true) * 100,
         'value-' . microtime(true) * 100
      ];
   }

   private function getMemcached() {
      $memcache = new Memcache();
      $memcache->pconnect('localhost', 11211);

      return $memcache;
   }
}
