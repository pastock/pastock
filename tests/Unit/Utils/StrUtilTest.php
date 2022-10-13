<?php

namespace Tests\Unit\Utils;

use App\Utils\StrUtil;
use PHPUnit\Framework\TestCase;

class StrUtilTest extends TestCase
{
    public function testNoSpaceNoReplace(): void
    {
        $this->assertSame('ab', StrUtil::replaceMultiSpaceToOne('ab'));
    }

    public function testOneSpaceNoReplace(): void
    {
        $this->assertSame('a b', StrUtil::replaceMultiSpaceToOne('a b'));
    }

    public function testFiveSpaceReplaceToOne(): void
    {
        $this->assertSame('a b', StrUtil::replaceMultiSpaceToOne('a     b'));
    }

    public function testTwoFiveSpaceReplaceToOne(): void
    {
        $this->assertSame('a b c', StrUtil::replaceMultiSpaceToOne('a     b     c'));
    }
}
