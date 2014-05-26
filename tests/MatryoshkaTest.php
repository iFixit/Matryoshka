<?php

require_once __DIR__ . '/../library/iFixit/Matryoshka.php';

use iFixit\Matryoshka;

Matryoshka::autoload();

class MatryoshkaTest extends PHPUnit_Framework_TestCase {
   public function testHierarchy() {
      $backends = [
         new Matryoshka\MemoryArray(),
         Matryoshka\Memcache::create($this->getMemcache())
      ];
      $hierarchy = new Matryoshka\Hierarchy($backends);
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

   public function testScoped() {
      $memoryCache = new Matryoshka\MemoryArray();
      $scope = 'scope';
      $scopedCache = new Matryoshka\Scoped($memoryCache,
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

   public function testMemcache() {
      $cache = Matryoshka\Memcache::create($this->getMemcache());
      list($key, $value) = $this->getRandomKeyValue();
      $this->assertTrue($cache->set($key, $value, 1));
      // Wait for it to expire.
      sleep(2);

      $this->assertNull($cache->get($key));
   }

   public function testEnabled() {
      $cache = new Matryoshka\Enabled(new Matryoshka\MemoryArray());
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

   public function testStats() {
      $cache = new Matryoshka\Stats(new Matryoshka\MemoryArray());
      list($key, $value) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      foreach ($cache->getStats() as $stat => $value) {
         $this->assertSame(0, $value, $stat);
      }

      $start = microtime(true);

      $cache->add($key, $value);
      $cache->set($key, 5);
      $cache->increment($key, 1);
      $cache->decrement($key, 1);
      $cache->get($key);
      $cache->getMultiple([$key => '', $key2 => '']);
      $cache->delete($key);

      $end = microtime(true);
      $maxTime = $end - $start;

      foreach ($cache->getStats() as $stat => $value) {
         if ($stat == 'getMultiple_key_count') {
            $this->assertSame(2, $value, $stat);
         } else if (strpos($stat, '_count') !== false) {
            $this->assertSame(1, $value, $stat);
         } else if (strpos($stat, '_time') !== false) {
            $this->assertGreaterThan(0, $value, $stat);
            $this->assertLessThan($maxTime, $value, $stat);
         }
      }

      $cache->get($key);
      $stats = $cache->getStats();

      $this->assertSame(2, $stats['get_count']);
      $this->assertSame(1, $stats['get_hit_count']);
   }

   public function testKeyShortener() {
      $maxLength = 50;
      $intactKeyLength = $maxLength - Matryoshka\KeyShortener::MD5_STRLEN;
      $memoryCache = new TestMemoryArray();
      $cache = new Matryoshka\KeyShortener($memoryCache, $maxLength);

      $keys = [
         'short',
         str_repeat('a', $maxLength),
         str_repeat('a', $maxLength + 1),
         str_repeat('a', $maxLength * 10)
      ];

      foreach ($keys as $key) {
         $cache->set($key, $key);
      }

      $cachedValues = $memoryCache->getCache();

      $this->assertCount(count($keys), $cachedValues);

      foreach ($cachedValues as $shortenedKey => $originalKey) {
         $this->assertLessThanOrEqual($maxLength, strlen($shortenedKey));

         $this->assertSame(substr($originalKey, 0, $intactKeyLength),
          substr($shortenedKey, 0, $intactKeyLength));
      }
   }

   /**
    * It's hard to test them individually so we can just test all 3 at once.
    */
   public function testgetSetDelete() {
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();
      list($key3, $value3) = $this->getRandomKeyValue();
      foreach ($this->getAllBackends() as $type => $cache) {
         $this->assertNull($cache->get($key1), $type);
         $this->assertTrue($cache->set($key1, $value1), $type);
         $this->assertSame($value1, $cache->get($key1), $type);
         $this->assertTrue($cache->set($key1, $value1), $type);
         $this->assertTrue($cache->delete($key1), $type);
         $this->assertFalse($cache->delete($key1), $type);
         $this->assertNull($cache->get($key1), $type);

         $this->assertTrue($cache->set($key2, $value2), $type);
         $this->assertTrue($cache->set($key3, $value3), $type);
         $this->assertSame($value2, $cache->get($key2), $type);
         $this->assertSame($value3, $cache->get($key3), $type);
      }
   }

   public function testgetMultiple() {
      list($key1, $value1, $id1) = $this->getRandomKeyValueId();
      list($key2, $value2, $id2) = $this->getRandomKeyValueId();
      list($key3, $value3, $id3) = $this->getRandomKeyValueId();
      list($key4, $value4, $id4) = $this->getRandomKeyValueId();
      foreach ($this->getAllBackends() as $type => $cache) {
         list($found, $missed) = $cache->getMultiple([]);
         $this->assertEmpty($found, $type);
         $this->assertEmpty($missed, $type);

         $keys = [
            $key1 => $id1,
            $key2 => $id2,
            $key3 => $id3,
            $key4 => $id4
         ];
         list($found, $missed) = $cache->getMultiple($keys);
         $this->assertEmpty(array_filter($found), $type);
         $this->assertSame(array_keys($keys), array_keys($found), $type);
         $this->assertSame($keys, $missed, $type);

         $cache->set($key1, $value1);
         $cache->set($key2, $value2);
         $expectedFound = [$key1 => $value1, $key2 => $value2, $key3 => null,
          $key4 => null];
         $expectedMissed = [$key3 => $id3, $key4 => $id4];
         list($found, $missed) = $cache->getMultiple($keys);
         $this->assertSame($found, $expectedFound, $type);
         $this->assertSame($missed, $expectedMissed, $type);

         $cache->set($key3, $value3);
         $cache->set($key4, $value4);
         $expectedFound = [$key1 => $value1, $key2 => $value2, $key3 => $value3,
          $key4 => $value4];
         list($found, $missed) = $cache->getMultiple($keys);
         $this->assertSame($expectedFound, $found, $type);
         $this->assertEmpty($missed, $type);
      }
   }

   public function testadd() {
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();
      foreach ($this->getAllBackends() as $type => $cache) {
         $this->assertTrue($cache->add($key1, $value1), $type);
         $this->assertFalse($cache->add($key1, $value1), $type);
         $this->assertSame($value1, $cache->get($key1), $type);
         $this->assertTrue($cache->add($key2, $value2), $type);
         $this->assertSame($value2, $cache->get($key2), $type);

         $this->assertTrue($cache->delete($key1), $type);
         $this->assertNull($cache->get($key1), $type);
         $this->assertTrue($cache->add($key1, $value1), $type);
         $this->assertSame($value1, $cache->get($key1), $type);
         $this->assertSame($value2, $cache->get($key2), $type);
      }
   }

   public function testincrement() {
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();
      foreach ($this->getAllBackends() as $type => $cache) {
         $this->assertNull($cache->get($key1), $type);
         $currentValue = 0;
         foreach ([1, 5, 100] as $amount) {
            $currentValue += $amount;
            $this->assertSame($currentValue, $cache->increment($key1, $amount),
             "$type | $amount");
         }

         // This comes out as a string for Memcache so we must use
         // assertEquals rather than assertSame.
         $this->assertEquals($currentValue, $cache->get($key1), $type);

         $this->assertTrue($cache->delete($key1));
         $this->assertSame(7, $cache->increment($key1, 7), $type);

         // TODO: Memcache has some strange behavior with these values that
         // doesn't appear to match the docs. It might have to do with
         // compression.
         if ($type !== 'Memcache') {
            $invalidValues = [
               'string',
               ['array'],
               (object)['object' => 'value']
            ];
            foreach ($invalidValues as $invalidValue) {
               $this->assertTrue($cache->set($key2, $invalidValue), $type);
               $this->assertSame(1, $cache->increment($key2), $type);
               $this->assertSame(1, $cache->get($key2), $type);
            }
         }
      }
   }

   public function testdecrement() {
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();
      foreach ($this->getAllBackends() as $type => $cache) {
         $this->assertNull($cache->get($key1), $type);

         // TODO: Memcache values cannot be decremented below 0 so we must
         // start it out higher.
         if ($type === 'Memcache') {
            $currentValue = 400;
            $cache->set($key1, $currentValue);
         } else {
            $currentValue = 0;
         }
         foreach ([1, 5, 100] as $amount) {
            $currentValue -= $amount;
            $this->assertSame($currentValue, $cache->decrement($key1, $amount),
             "$type | $amount");
         }

         // This comes out as a string for Memcache so we must use
         // assertEquals rather than assertSame.
         $this->assertEquals($currentValue, $cache->get($key1), $type);

         $this->assertTrue($cache->delete($key1));

         // TODO: Memcache has some strange behavior with these values that
         // doesn't appear to match the docs.
         if ($type !== 'Memcache') {
            $this->assertSame(-7, $cache->decrement($key1, 7), $type);

            $invalidValues = [
               'string',
               ['array'],
               (object)['object' => 'value']
            ];
            foreach ($invalidValues as $invalidValue) {
               $this->assertTrue($cache->set($key2, $invalidValue), $type);
               $this->assertSame(-1, $cache->decrement($key2), $type);
               $this->assertSame(-1, $cache->get($key2), $type);
            }
         }
      }
   }

   public function testgetAndSet() {
      foreach ($this->getAllBackends() as $type => $cache) {
         list($key, $value) = $this->getRandomKeyValue();

         $this->assertNull($cache->get($key), $type);

         $hit = false;
         $callback = function() use ($value, &$hit) {
            $hit = true;
            return $value;
         };

         $getAndSetValue = $cache->getAndSet($key, $callback);

         $this->assertTrue($hit, $type);
         $this->assertSame($value, $getAndSetValue, $type);
         $this->assertSame($value, $cache->get($key), $type);

         $hit = false;
         $getAndSetValue = $cache->getAndSet($key, $callback);

         $this->assertFalse($hit, $type);
         $this->assertSame($value, $getAndSetValue, $type);
         $this->assertSame($value, $cache->get($key), $type);
      }
   }

   public function testgetAndSetMultiple() {
      foreach ($this->getAllBackends() as $type => $cache) {
         list($key1, $value1, $id1) = $this->getRandomKeyValueId();
         list($key2, $value2, $id2) = $this->getRandomKeyValueId();
         list($key3, $value3, $id3) = $this->getRandomKeyValueId();
         list($key4, $value4, $id4) = $this->getRandomKeyValueId();
         $keys = [
            $key1 => $id1,
            $key2 => $id2,
            $key3 => $id3,
            $key4 => $id4
         ];
         $keyToValue = [
            $key1 => $value1,
            $key2 => $value2,
            $key3 => $value3,
            $key4 => $value4
         ];
         $numMisses = -1;
         $callback = function($missing) use ($keyToValue, $type, &$numMisses) {
            $this->assertNotEmpty($missing, $type);
            $numMisses = count($missing);

            $result = [];

            foreach ($missing as $key => $id) {
               $result[$key] = $keyToValue[$key];
            }

            return $result;
         };

         $this->assertSame($keyToValue,
          $cache->getAndSetMultiple($keys, $callback), $type);
         $this->assertSame(count($keyToValue), $numMisses, $type);

         $numMisses = -1;
         $this->assertSame($keyToValue,
          $cache->getAndSetMultiple($keys, $callback), $type);
         $this->assertSame(-1, $numMisses, $type);

         $cache->delete($key2);
         $cache->delete($key3);
         $this->assertSame($keyToValue,
          $cache->getAndSetMultiple($keys, $callback), $type);
         $this->assertSame(2, $numMisses, $type);

         $numMisses = -1;
         $this->assertSame($keyToValue,
          $cache->getAndSetMultiple($keys, $callback), $type);
         $this->assertSame(-1, $numMisses, $type);

         $emptyCallbacks = [
            'empty' => function($missing) use (&$numMisses) {
               $numMisses = count($missing);
               return [];
            },
            'invalid values' => function($missing) use (&$numMisses) {
               $numMisses = count($missing);
               list($key1, $value1) = $this->getRandomKeyValue();
               list($key2, $value2) = $this->getRandomKeyValue();

               return [
                  $key1 => $value1,
                  $key2 => $value2
               ];
            }
         ];

         foreach ($emptyCallbacks as $type => $emptyCallback) {
            list($key1, $value1, $id1) = $this->getRandomKeyValueId();
            list($key2, $value2, $id2) = $this->getRandomKeyValueId();
            list($key3, $value3, $id3) = $this->getRandomKeyValueId();
            $keys = [
               $key1 => $id1,
               $key2 => $id2,
               $key3 => $id3
            ];

            $numMisses = -1;
            $result = $cache->getAndSetMultiple($keys, $emptyCallback);
            $this->assertEmpty($result, $type);
            $this->assertSame(count($keys), $numMisses, $type);

            // Make sure the false keys aren't set.
            foreach ($keys as $key => $id) {
               $this->assertNull($cache->get($key), $type);
            }
         }
      }
   }

   public function testlongKeys() {
      list($key, $value) = $this->getRandomKeyValue();
      // Make a super long key.
      $key = str_repeat($key, 100);

      foreach ($this->getAllBackends() as $type => $cache) {
         $this->assertNull($cache->get($key), $type);
         $this->assertTrue($cache->set($key, $value), $type);
         $this->assertSame($value, $cache->get($key), $type);

         $newKey = "{$key}-new";
         $newValue = "{$value}-new";
         $this->assertNull($cache->get($newKey), $type);
         $this->assertTrue($cache->set($newKey, $newValue), $type);
      }
   }

   private function getRandomKeyValue() {
      return [
         'key-' . rand(),
         'value-' . rand()
      ];
   }

   private function getRandomKeyValueId() {
      return [
         'key-' . rand(),
         'value-' . rand(),
         'id-' . rand()
      ];
   }

   private function getMemcache() {
      $memcache = new Memcache();
      $memcache->pconnect('localhost', 11211);

      return $memcache;
   }

   private function getAllBackends() {
      return [
         'Enabled' => new Matryoshka\Enabled(new Matryoshka\MemoryArray()),
         'Hierarchy' => new Matryoshka\Hierarchy([
            new Matryoshka\MemoryArray(),
            new Matryoshka\MemoryArray()
         ]),
         'KeyShortener' => new Matryoshka\KeyShortener(new Matryoshka\MemoryArray(), 40),
         'Local' => new Matryoshka\Local(new Matryoshka\MemoryArray()),
         'Memcache' => Matryoshka\Memcache::create($this->getMemcache()),
         'MemoryArray' => new Matryoshka\MemoryArray(),
         'Prefixed' => new Matryoshka\Prefixed(new Matryoshka\MemoryArray(), 'prefix'),
         'Scoped' => new Matryoshka\Scoped(new Matryoshka\MemoryArray(), 'scope'),
         'Stats' => new Matryoshka\Stats(new Matryoshka\MemoryArray())
      ];
   }
}

// Exposes the array of cached values.
class TestMemoryArray extends Matryoshka\MemoryArray {
   public function getCache() {
      return $this->cache;
   }
}
