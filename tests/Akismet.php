<?php declare(strict_types=1);
namespace Akismet\Tests;

use PHPUnit\Framework\TestCase;

use Akismet\API;

final class Akismet extends TestCase {
  public API $instance;
  
  public function testNothing(): void
  {
    $this->assertSame(false, false);
  }
}
