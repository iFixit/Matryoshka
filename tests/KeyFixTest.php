<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;
use iFixit\Matryoshka\Memcached;

class KeyFixTest extends AbstractBackendTest {
   protected function getBackend() {
      return new Matryoshka\KeyFix(new Matryoshka\Ephemeral(), 40, Memcached::INVALID_CHARS_REGEX);
   }

   public function testKeyShorten() {
      $maxLength = 50;
      $intactKeyLength = $maxLength - Matryoshka\KeyFix::MD5_STRLEN;
      list($key) = $this->getRandomKeyValue();
      $longKey = str_repeat($key, 10);
      $memoryCache = new TestEphemeral();
      $cache = new Matryoshka\KeyFix($memoryCache, $maxLength,  Memcached::INVALID_CHARS_REGEX);

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
      }

      // Make sure that setMultiple produces the same keys.
      $memoryCache->clear();
      $cache->setMultiple(array_combine($keys, $keys));
      $this->assertSame($cachedValues, $memoryCache->getCache());
   }

   public function testValidation() {
      try {
         new Matryoshka\KeyFix(new TestEphemeral(), 5, Memcached::INVALID_CHARS_REGEX);
         $this->fail("Doesn't throw InvalidArgumentException");
      } catch (Throwable $e) {
         // Do nothing.
      }

      try {
         new Matryoshka\KeyFix(new TestEphemeral(), 40, '');
         $this->fail("Doesn't throw InvalidArgumentException");
      } catch (Throwable $e) {
         // Do nothing.
      }
   }

   public function testNoBadChars() {
      $memoryCache = new TestEphemeral();
      $cache = new Matryoshka\KeyFix($memoryCache, 40,  Memcached::INVALID_CHARS_REGEX);

      list($goodKey) = $this->getRandomKeyValue();
      $cache->set($goodKey, $goodKey);

      foreach ($memoryCache->getCache() as $fixed => $original) {
         $this->assertSame($fixed, $original);
      }
   }

   public function testCustomBadChars() {
      $badChar = '/a/';

      $memoryCache = new TestEphemeral();
      $cache = new Matryoshka\KeyFix($memoryCache, 40, $badChar);

      list($key) = $this->getRandomKeyValue();
      $badKey = $key . $badChar;

      $cache->set($badKey, $badKey);

      foreach ($memoryCache->getCache() as $fixed => $original) {
         $this->assertNotSame($fixed, $original);
      }
   }
}
