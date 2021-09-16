<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class KeyShortenTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\KeyShorten(new Matryoshka\Ephemeral(), 40);
   }

   public function testKeyShorten() {
      $maxLength = 50;
      $intactKeyLength = $maxLength - Matryoshka\KeyShorten::MD5_STRLEN;
      list($key) = $this->getRandomKeyValue();
      $longKey = str_repeat($key, 10);
      $memoryCache = new TestEphemeral();
      $cache = new Matryoshka\KeyShorten($memoryCache, $maxLength);

      $keys = [
         'short',
         substr($longKey, 0, $maxLength),
         substr($longKey, 0, $maxLength + 1),
         substr($longKey, 0, $maxLength * 10),
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

      // Make sure that setMultiple produces the same keys.
      $memoryCache->clear();
      $cache->setMultiple(array_combine($keys, $keys));
      $this->assertSame($cachedValues, $memoryCache->getCache());
   }

   public function testKeyShortenLength() {
      try {
         new Matryoshka\KeyShorten(new TestEphemeral(), 5);
         $this->fail("Doesn't throw InvalidArgumentException");
      } catch (InvalidArgumentException $e) {
         // Do nothing.
      }
   }

   public function testAbsoluteKey() {
      $backend = $this->getBackend();
      [$key, $value] = $this->getRandomKeyValue();
      $longKey = str_repeat($key, 10);

      $absoluteKey = $backend->getAbsoluteKey($longKey);

      $this->assertTrue(strlen($absoluteKey) <= 40);
   }
}
