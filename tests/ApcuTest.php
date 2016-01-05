<?php

require_once 'AbstractBackendTest.php';

use iFixit\Matryoshka;

class ApcuTest extends AbstractBackendTest {
   protected function setUp() {
      if (!Matryoshka\APCu::isAvailable()) {
         $this->markTestSkipped('Backend not available!');
      }

      return parent::setUp();
   }

   protected function getBackend() {
      return new Matryoshka\APCu();
   }
}
