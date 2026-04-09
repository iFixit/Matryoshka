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
      $scopedCache = new Matryoshka\Scope($memoryCache, $scope);
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

      $scopedCache = new Matryoshka\Scope($memoryCache, $scope);
      $this->assertSame($value1, $scopedCache->get($key1));
   }

   public function testScopeShortCircuitGet() {
      $scope = 'scope';
      $mockBackend = $this->getMockBuilder(Matryoshka\Ephemeral::class)
            ->setMethods(['get'])
            ->getMock();

      // Assert get() is called only once because a missing scope value means
      // that all underlying values are also missing.
      $mockBackend->expects($this->once())
         ->method('get')
         ->with($this->stringContains($scope))
         ->willReturn(Matryoshka\Backend::MISS);
      $scopedCache = new Matryoshka\Scope($mockBackend, $scope);

      $key = (string)rand(1, 100000);
      $this->assertNull($scopedCache->get($key));
   }

   public function testScopeShortCircuitGetMultiple() {
      $scope = 'scope';
      $mockBackend = $this->getMockBuilder(Matryoshka\Ephemeral::class)
            ->setMethods(['get', 'getMultiple'])
            ->getMock();

      // Assert get() is called only once because a missing scope value means
      // that all underlying values are also missing.
      $mockBackend->expects($this->once())
         ->method('get')
         ->with($this->stringContains($scope))
         ->willReturn(Matryoshka\Backend::MISS);
      // Assert getMultiple() is never called because a missi
      // we don't need to check individual keys.
      $mockBackend->expects($this->never())
         ->method('getMultiple');

      $scopedCache = new Matryoshka\Scope($mockBackend, $scope);

      $key = (string)rand(1, 100000);
      $keys = [$key => 'no matter'];
      $this->assertSame(
         [[$key => Matryoshka\Backend::MISS], $keys],
         $scopedCache->getMultiple($keys)
      );
   }

   public function testAbsoluteKey() {
      $memoryCache = new Matryoshka\Ephemeral();
      $scope = 'scope';
      $scopedCache = new Matryoshka\Scope($memoryCache, $scope);
      [$key] = $this->getRandomKeyValue();

      $this->assertEquals($scopedCache->getScopePrefix() . $key, $scopedCache->getAbsoluteKey($key));
   }

   public function testPrefixFirstWriterWins() {
      $racing = new RacingBackend(new Matryoshka\Ephemeral());
      $scope = new Matryoshka\Scope($racing, 'test-scope');

      $competitorPrefix = 'competitor-won';

      $racing->afterNextGet(function($key) use ($racing, $competitorPrefix) {
         $racing->set($key, $competitorPrefix);
      });

      $prefix = $scope->getScopePrefix();

      $this->assertSame("{$competitorPrefix}-", $prefix);
      $this->assertSame($competitorPrefix, $racing->get('scope-test-scope'));
   }

   public function testPrefixInit() {
      $inner = new Matryoshka\Ephemeral();
      $scope = new Matryoshka\Scope($inner, 'test-scope');

      $prefix = $scope->getScopePrefix();

      $this->assertNotEmpty($prefix);
      $this->assertStringEndsWith('-', $prefix);
      $this->assertSame($prefix, $scope->getScopePrefix());
   }

   public function testDeleteScopeOverwrites() {
      $inner = new Matryoshka\Ephemeral();
      $scope = new Matryoshka\Scope($inner, 'test-scope');

      $originalPrefix = $scope->getScopePrefix();
      $scope->deleteScope();
      $newPrefix = $scope->getScopePrefix();

      $this->assertNotSame($originalPrefix, $newPrefix);
   }

   public function testPrefixRacePreservesData() {
      $racing = new RacingBackend(new Matryoshka\Ephemeral());
      $scope = new Matryoshka\Scope($racing, 'test-scope');

      $competitorPrefix = 'competitor-won';

      $racing->afterNextGet(function($key) use ($racing, $competitorPrefix) {
         $racing->set($key, $competitorPrefix);
         $racing->set("{$competitorPrefix}-user-data", 'competitor-data');
      });

      $scope->getScopePrefix();

      $this->assertSame('competitor-data', $scope->get('user-data'));
   }

   /**
    * If add() fails and the re-fetch also misses (e.g. the backend lost
    * the key between add and get), the generated prefix is still used.
    */
   public function testPrefixNonEmptyOnAddFailure() {
      $backend = new class extends Matryoshka\Ephemeral {
         public function add($key, $value, $expiration = 0) {
            return false;
         }
      };
      $scope = new Matryoshka\Scope($backend, 'test-scope');

      $prefix = $scope->getScopePrefix();

      $this->assertNotSame('-', $prefix);
      $this->assertStringEndsWith('-', $prefix);
   }
}
