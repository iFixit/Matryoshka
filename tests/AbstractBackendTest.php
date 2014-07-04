<?php

require_once __DIR__ . '/../library/iFixit/Matryoshka.php';

use iFixit\Matryoshka;

Matryoshka::autoload();

abstract class AbstractBackendTest extends PHPUnit_Framework_TestCase {
   protected abstract function getBackend();

   /**
    * It's hard to test them individually so we can just test all 3 at once.
    */
   public function testgetSetDelete() {
      $backend = $this->getBackend();
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();
      list($key3, $value3) = $this->getRandomKeyValue();

      $this->assertNull($backend->get($key1));
      $this->assertTrue($backend->set($key1, $value1));
      $this->assertSame($value1, $backend->get($key1));
      $this->assertTrue($backend->set($key1, $value1));
      $this->assertTrue($backend->delete($key1));
      $this->assertFalse($backend->delete($key1));
      $this->assertNull($backend->get($key1));

      $this->assertTrue($backend->set($key2, $value2));
      $this->assertTrue($backend->set($key3, $value3));
      $this->assertSame($value2, $backend->get($key2));
      $this->assertSame($value3, $backend->get($key3));
   }

   public function testgetMultiple() {
      $backend = $this->getBackend();
      list($key1, $value1, $id1) = $this->getRandomKeyValueId();
      list($key2, $value2, $id2) = $this->getRandomKeyValueId();
      list($key3, $value3, $id3) = $this->getRandomKeyValueId();
      list($key4, $value4, $id4) = $this->getRandomKeyValueId();

      list($found, $missed) = $backend->getMultiple([]);
      $this->assertEmpty($found);
      $this->assertEmpty($missed);

      $keys = [
         $key1 => $id1,
         $key2 => $id2,
         $key3 => $id3,
         $key4 => $id4
      ];
      list($found, $missed) = $backend->getMultiple($keys);
      $this->assertEmpty(array_filter($found));
      $this->assertSame(array_keys($keys), array_keys($found));
      $this->assertSame($keys, $missed);

      $backend->set($key1, $value1);
      $backend->set($key2, $value2);
      $expectedFound = [$key1 => $value1, $key2 => $value2, $key3 => null,
       $key4 => null];
      $expectedMissed = [$key3 => $id3, $key4 => $id4];
      list($found, $missed) = $backend->getMultiple($keys);
      $this->assertSame($found, $expectedFound);
      $this->assertSame($missed, $expectedMissed);

      $backend->set($key3, $value3);
      $backend->set($key4, $value4);
      $expectedFound = [$key1 => $value1, $key2 => $value2, $key3 => $value3,
       $key4 => $value4];
      list($found, $missed) = $backend->getMultiple($keys);
      $this->assertSame($expectedFound, $found);
      $this->assertEmpty($missed);
   }

   public function testadd() {
      $backend = $this->getBackend();
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      $this->assertTrue($backend->add($key1, $value1));
      $this->assertFalse($backend->add($key1, $value1));
      $this->assertSame($value1, $backend->get($key1));
      $this->assertTrue($backend->add($key2, $value2));
      $this->assertSame($value2, $backend->get($key2));

      $this->assertTrue($backend->delete($key1));
      $this->assertNull($backend->get($key1));
      $this->assertTrue($backend->add($key1, $value1));
      $this->assertSame($value1, $backend->get($key1));
      $this->assertSame($value2, $backend->get($key2));
   }

   public function testincrement() {
      $backend = $this->getBackend();
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      $this->assertNull($backend->get($key1));
      $currentValue = 0;
      foreach ([1, 5, 100] as $amount) {
         $currentValue += $amount;
         $this->assertSame($currentValue, $backend->increment($key1, $amount),
          "Amount: $amount");
      }

      // This comes out as a string for Membackend so we must use
      // assertEquals rather than assertSame.
      $this->assertEquals($currentValue, $backend->get($key1));

      $this->assertTrue($backend->delete($key1));
      $this->assertSame(7, $backend->increment($key1, 7));

      // TODO: Memcache has some strange behavior with these values that
      // doesn't appear to match the docs. It might have to do with
      // compression.
      if (get_called_class() !== 'MemcacheTest') {
         $invalidValues = [
            'string',
            ['array'],
            (object)['object' => 'value']
         ];
         foreach ($invalidValues as $invalidValue) {
            $this->assertTrue($backend->set($key2, $invalidValue));
            $this->assertSame(1, $backend->increment($key2));
            $this->assertSame(1, $backend->get($key2));
         }
      }
   }

   public function testdecrement() {
      $backend = $this->getBackend();
      list($key1, $value1) = $this->getRandomKeyValue();
      list($key2, $value2) = $this->getRandomKeyValue();

      $this->assertNull($backend->get($key1));

      // TODO: Memcache values cannot be decremented below 0 so we must
      // start it out higher.
      if (get_called_class() === 'MemcacheTest') {
         $currentValue = 400;
         $backend->set($key1, $currentValue);
      } else {
         $currentValue = 0;
      }
      foreach ([1, 5, 100] as $amount) {
         $currentValue -= $amount;
         $this->assertSame($currentValue, $backend->decrement($key1, $amount),
          "Amount: $amount");
      }

      // This comes out as a string for Membackend so we must use
      // assertEquals rather than assertSame.
      $this->assertEquals($currentValue, $backend->get($key1));

      $this->assertTrue($backend->delete($key1));

      // TODO: Memcache has some strange behavior with these values that
      // doesn't appear to match the docs.
      if (get_called_class() !== 'MemcacheTest') {
         $this->assertSame(-7, $backend->decrement($key1, 7));

         $invalidValues = [
            'string',
            ['array'],
            (object)['object' => 'value']
         ];
         foreach ($invalidValues as $invalidValue) {
            $this->assertTrue($backend->set($key2, $invalidValue));
            $this->assertSame(-1, $backend->decrement($key2));
            $this->assertSame(-1, $backend->get($key2));
         }
      }
   }

   public function testgetAndSet() {
      $backend = $this->getBackend();
      list($key, $value) = $this->getRandomKeyValue();

      $this->assertNull($backend->get($key));

      $miss = false;
      $callback = function() use ($value, &$miss) {
         $miss = true;
         return $value;
      };

      $getAndSetValue = $backend->getAndSet($key, $callback);

      $this->assertTrue($miss);
      $this->assertSame($value, $getAndSetValue);
      $this->assertSame($value, $backend->get($key));

      $miss = false;
      $getAndSetValue = $backend->getAndSet($key, $callback);

      $this->assertFalse($miss);
      $this->assertSame($value, $getAndSetValue);
      $this->assertSame($value, $backend->get($key));

      $miss = false;
      list(, $newValue) = $this->getRandomKeyValue();
      $callback = function() use ($newValue, &$miss) {
         $miss = true;
         return $newValue;
      };

      // Try resetting the backend with getAndSet.
      $getAndSetValue = $backend->getAndSet($key, $callback, 0, $reset = true);

      $this->assertTrue($miss);
      $this->assertSame($newValue, $getAndSetValue);
      $this->assertSame($newValue, $backend->get($key));


      list($key, $value) = $this->getRandomKeyValue();
      $this->assertNull($backend->get($key));
      $backend->set($key, $value);

      // Return null from the callback and make sure the value isn't updated.
      $getAndSetValue = $backend->getAndSet($key,
       function() { return null; }, 0, $reset = true);

      $this->assertNull($getAndSetValue);
      $this->assertSame($value, $backend->get($key));
   }

   public function testgetAndSetMultiple() {
      $backend = $this->getBackend();
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
      $callback = function($missing) use ($keyToValue, &$numMisses) {
         $this->assertNotEmpty($missing);
         $numMisses = count($missing);

         $result = [];

         foreach ($missing as $key => $id) {
            $result[$key] = $keyToValue[$key];
         }

         return $result;
      };

      $this->assertSame($keyToValue,
       $backend->getAndSetMultiple($keys, $callback));
      $this->assertSame(count($keyToValue), $numMisses);

      $numMisses = -1;
      $this->assertSame($keyToValue,
       $backend->getAndSetMultiple($keys, $callback));
      $this->assertSame(-1, $numMisses);

      $backend->delete($key2);
      $backend->delete($key3);
      $this->assertSame($keyToValue,
       $backend->getAndSetMultiple($keys, $callback));
      $this->assertSame(2, $numMisses);

      $numMisses = -1;
      $this->assertSame($keyToValue,
       $backend->getAndSetMultiple($keys, $callback));
      $this->assertSame(-1, $numMisses);

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
         $result = $backend->getAndSetMultiple($keys, $emptyCallback);
         $this->assertEmpty($result);
         $this->assertSame(count($keys), $numMisses);

         // Make sure the false keys aren't set.
         foreach ($keys as $key => $id) {
            $this->assertNull($backend->get($key));
         }
      }
   }

   public function testlongKeys() {
      $backend = $this->getBackend();
      list($key, $value) = $this->getRandomKeyValue();
      // Make a super long key.
      $key = str_repeat($key, 100);

      $this->assertNull($backend->get($key));
      $this->assertTrue($backend->set($key, $value));
      $this->assertSame($value, $backend->get($key));

      $newKey = "{$key}-new";
      $newValue = "{$value}-new";
      $this->assertNull($backend->get($newKey));
      $this->assertTrue($backend->set($newKey, $newValue));
   }

   protected function getRandomKeyValue() {
      return [
         'key-' . rand(),
         'value-' . rand()
      ];
   }

   protected function getRandomKeyValueId() {
      return [
         'key-' . rand(),
         'value-' . rand(),
         'id-' . rand()
      ];
   }

   protected function getMemcache() {
      $memcache = new Memcache();
      $memcache->pconnect('localhost', 11211);

      return $memcache;
   }
}

// Exposes the array of cached values.
class TestMemoryArray extends Matryoshka\MemoryArray {
   public function getCache() {
      return $this->cache;
   }
}
